<?php
$data = json_decode(file_get_contents('results.json'), true);

?>
<html>
<head>
    <title>Epics and issues</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css'>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>

    <style>
        body {
            color: white;
            background-color: #272c30;
            min-height: 75rem;
            padding-top: 4.5rem;
        }

        a {
            color: #85c0ff;
        }

        div.max-height {
            max-height: 400px;
            /*width: 100%;*/
            overflow-y: auto;
            overflow-x: hidden;
        }

        hr {
            border-top-color: #aaa;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
    <a class="navbar-brand" href="#">Top</a>
    <div class="collapse navbar-collapse" id="navbarCollapse">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link" href="#epics">Epics</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#empty_epics">Empty epics</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#duplicates">Duplicated issues</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#issues_without_epic">Issues without epic</a>
            </li>
        </ul>
    </div>
</nav>
<div class="container" >
    <h1>Epics and issues</h1>
    <hr>
    <h2 id="epics">Epics</h2>
    <div class="row">
        <div class="max-height">
            <table class="table table-dark table-striped table-hover">
                <thead>
                <tr>
                    <th>Epic</th>
                    <th>Related issues</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach($data['epics'] as $number => $epic_data) {
                    $epic_data_issues = '';
                    foreach($epic_data['issues'] as $issue) {
                        $title = $issue;
                        if (isset($data['all_issues'][$issue])) {
                            $title = str_replace('"', "'", $data['all_issues'][$issue]['title']);
                        }
                        $epic_data_issues .= '<a href="https://github.com/PrestaShop/PrestaShop/issues/'.$issue.'" 
                        data-toggle="tooltip" data-placement="top" title="'.$title.'"
                        target="_blank">#'.$issue.'</a> ';

                    }
                    echo '
                    <tr>
                        <td width="420">
                            <a href="https://github.com/PrestaShop/PrestaShop/issues/'.$number.'" target="_blank">#'.$number.' - '.$epic_data['title'].'</a>
                        </td>
                        <td>
                            '.$epic_data_issues.'
                        </td>
                    </tr>
                    ';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <hr>
    <h2 id="empty_epics">Empty Epics</h2>
    <p>These Epics have been detected as empty. To remove them from this section, make sure they have a
        checkable list of issues in the body.</p>
    <div class="row">
        <?php
        foreach($data['empty_epics'] as $empty_epic => $empty_epic_title) {
            echo '<div class="col-4">
                    <a href="https://github.com/PrestaShop/PrestaShop/issues/'.$empty_epic.'" target="_blank">#'.$empty_epic.' - '.$empty_epic_title.'</a>
                </div>';
        }
        ?>
    </div>
    <hr>
    <h2 id="duplicates">Issues duplicated</h2>
    <p>Issues in at least 2 different Epics.</p>
    <div class="row">
        <div class="max-height">
            <table class='table table-dark table-striped table-hover'>
                <thead>
                <tr>
                    <th>Epic 1</th>
                    <th>Epic 2</th>
                    <th>Issues</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach($data['duplicates'] as $duplicates) {
                    $issues_duplicated = '';
                    foreach($duplicates['issues'] as $duplicated_issue) {
                        $title = '';
                        if (isset($data['all_issues'][$duplicated_issue])) {
                            $title = str_replace('"', "'", $data['all_issues'][$duplicated_issue]['title']);
                        }
                        $issues_duplicated .= '
                            <a href="https://github.com/PrestaShop/PrestaShop/issues/'.$duplicated_issue.'"
                            data-toggle="tooltip" data-placement="top" title="'.$title.'" 
                            target="_blank">'.$duplicated_issue.'</a>
                            ';
                    }
                    $title_epic_1 = $duplicates['epic 1'];
                    if (in_array($duplicates['epic 1'], array_keys($data['epics']))) {
                        $title_epic_1 = '#'.$duplicates['epic 1'].' - '.$data['epics'][$duplicates['epic 1']]['title'];
                    }
                    $title_epic_2 = $duplicates['epic 2'];
                    if (in_array($duplicates['epic 2'], array_keys($data['epics']))) {
                        $title_epic_2 = '#'.$duplicates['epic 2'].' - '.$data['epics'][$duplicates['epic 2']]['title'];
                    }
                    echo '<tr>
                <td>
                    <a href="https://github.com/PrestaShop/PrestaShop/issues/'.$duplicates['epic 1'].'" target="_blank">'.$title_epic_1.'</a>
                </td>
                <td>
                    <a href="https://github.com/PrestaShop/PrestaShop/issues/'.$duplicates['epic 2'].'" target="_blank">'.$title_epic_2.'</a>
                </td>
                <td>
                    '.$issues_duplicated.'
                </td>
    </tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <hr>
    <h2 id="issues_without_epic">Issues without Epic</h2>
    <div class="row">
        <div class="max-height">
            <?php
            foreach($data['issues_not_in_epic'] as $issue_number => $issue_data) {
                echo '
                    <a href="https://github.com/PrestaShop/PrestaShop/issues/'.$issue_number.'" 
                        data-toggle="tooltip" data-placement="top" title="'.$issue_data['title'].'"                    
                    target="_blank">#'.$issue_number.'</a>
                    ';
            }
            ?>
        </div>
    </div>
</div>
<script>
  $(function () {
    $('[data-toggle="tooltip"]').tooltip();
  });
</script>
</body>
</html>
