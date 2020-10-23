<?php
use Github\Client;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

$client = new Client();
$client->authenticate(GITHUB_TOKEN, null, Github\Client::AUTH_ACCESS_TOKEN);

$after = null;

$query = '
{
    repository(name: "PrestaShop", owner: "PrestaShop") {
        issues(labels: "Epic", first: 100, orderBy: {field: CREATED_AT, direction: ASC}%AFTER%) {
          edges {
            cursor
            node {
              number
              title
              body
              createdAt
            }
          }
        }
    }
}
';

// get all EPIC
$issues_data = $client->api('graphql')->execute(str_replace('%AFTER%', '', $query));

$pattern = '/\[(x| )\].*#?(\d{4,}+)/iU';

$epics = [];
$list_issues = [];

while (count($issues_data['data']['repository']['issues']['edges']) > 0) {
    $issues = $issues_data['data']['repository']['issues']['edges'];
    echo sprintf("Found %s Epics starting at %s...%s", count($issues), $issues[0]['node']['createdAt'], PHP_EOL);

    foreach ($issues as $issue) {
        //putting the cursor to iterate on the next results
        $after = $issue['cursor'];
        //finding the bits "[ ] #xxxx..." in the bodyText
        preg_match_all($pattern, $issue['node']['body'], $results);
        if (isset($results[2])) {
            $epics[$issue['node']['number']]['title'] = $issue['node']['title'];
            $epics[$issue['node']['number']]['issues'] = [];
            foreach ($results[2] as $linked_issue) {
                $epics[$issue['node']['number']]['issues'][] = $linked_issue;
            }
        }
    }

    //relaunch the query with the next batch
    if (count($issues) == 100) {
        $issues_data = $client->api('graphql')->execute(str_replace('%AFTER%', ', after: "' . $after . '"', $query));
    } else {
        break;
    }
};

$duplicates = [];
$all_issues = [];
//filter all issues in epics to find issues in multiple epics
foreach ($epics as $epic_number => $epic_data) {
    if (count($epic_data['issues']) > 0) {
        foreach($duplicates as $duplicate) {
            if ($duplicate['epic 2'] == $epic_number) {
                //we've already done this one
                continue;
            }
        }
        $epic_issues = $epic_data['issues'];
        foreach ($epics as $epic_number_dup => $epic_data_dup) {
            if (count($epic_data_dup['issues']) > 0) {
                if ($epic_number == $epic_number_dup) {
                    //we don't compare the array with itself
                    continue;
                }
                $epic_issues_dup = $epic_data_dup['issues'];
                $dups = array_intersect($epic_issues, $epic_issues_dup);
                if (count($dups) > 0) {
                    $duplicates[] = [
                        'epic 1' => $epic_number,
                        'epic 2' => $epic_number_dup,
                        'issues' => $dups,
                    ];
                }
            }
        }
    }
}

//empty epics
$empty_epics = [];
foreach ($epics as $number => $epic) {
    if (count($epic['issues']) == 0) {
        unset($epics[$number]);
        $empty_epics[$number] = $epic['title'];
    }
}

//find issues not linked to an EPIC
$after = null;
$query = '
{
repository(name: "PrestaShop", owner: "PrestaShop") {
issues(first: 100, orderBy: {field: CREATED_AT, direction: ASC}, states: OPEN%AFTER%) {
  edges {
    cursor
    node {
      number
      title
      labels(first: 100) {
        nodes {
          name
        }
      }
    }
  }
}
}
}
';
// get all issues
$issues_not_in_epic = [];
$issues_data = $client->api('graphql')->execute(str_replace('%AFTER%', '', $query));

$i = 1;
while (count($issues_data['data']['repository']['issues']['edges']) > 0) {
    $issues = $issues_data['data']['repository']['issues']['edges'];
    echo sprintf("Found %s issues...(batch #%s)%s", count($issues), $i, PHP_EOL);
    foreach ($issues as $issue) {
        $all_issues[$issue['node']['number']] = [
            'title' => $issue['node']['title'],
        ];
        $after = $issue['cursor'];
        // is this issue already in an EPIC ?
        if (in_array($issue['node']['number'], $list_issues)) {
            //we skip it
            continue;
        }
        $labels = $issue['node']['labels']['nodes'];
        $skippable = 0;
        foreach ($labels as $label) {
            if (in_array($label['name'], ['TBS', 'To Do', 'TBR'])) {
                $skippable ++;
            }
            if (in_array($label['name'], ['Bug', 'Improvement', 'Feature'])) {
                $skippable ++;
            }
        }
        //this issue doesn't have the label 'TBS', 'To Do', 'Bug', 'Improvement', 'Feature'
        if ($skippable < 2) {
            continue;
        }
        //this issue is not in an Epic, and has one of the labels Bug/Improvement/Feature AND one of the labels To Do/TBS
        $issue_labels = [];
        foreach ($labels as $label) {
            $issue_labels[] = $label['name'];
        }
        $issues_not_in_epic[$issue['node']['number']] = [
            'title' => $issue['node']['title'],
            'labels' => implode(',', $issue_labels)
        ];
    }

    //relaunch the query with the next batch
    $issues_data = $client->api('graphql')->execute(str_replace('%AFTER%', ', after: "' . $after . '"', $query));
    $i++;
};


$final['epics'] = $epics;
$final['empty_epics'] = $empty_epics;
$final['duplicates'] = $duplicates;
$final['all_issues'] = $all_issues;
$final['issues_not_in_epic'] = $issues_not_in_epic;

$fh = fopen('results.json', 'w');
fwrite($fh, json_encode($final));
fclose($fh);
