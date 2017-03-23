<?php

/*
 * Poggit
 *
 * Copyright (C) 2016-2017 Poggit
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace poggit\ci\api;

use poggit\ci\builder\ProjectBuilder;
use poggit\module\AjaxModule;
use poggit\Poggit;
use poggit\utils\internet\MysqlUtils;

class SearchBuildAjax extends AjaxModule {
    private $projectResults = [];

    protected function impl() {
        // read post fields
        if(!isset($_POST["search"]) || !preg_match('%^[A-Za-z0-9_]{2,}$%', $_POST["search"])) $this->errorBadRequest("Invalid search field 'search'");

        $searchstring = "%" . $_POST["search"] . "%";
        foreach(MysqlUtils::query("SELECT  p.name AS projectName, r.owner as repoOwner, r.name AS repoName, p.projectId as projectId,
            p.type as projectType, p.framework as projectFramework
            FROM projects p INNER JOIN repos r ON p.repoId = r.repoId
            WHERE (r.name LIKE ? OR r.owner LIKE ? OR p.name LIKE ?) AND private = 0 AND r.build > 0 ORDER BY projectId DESC",
            "sss", $searchstring, $searchstring, $searchstring) as $row) {
            $row = (object) $row;
            $projectId = $row->projectId = (int) $row->projectId;
            $row->projectType = ProjectBuilder::$PROJECT_TYPE_HUMAN[$row->projectType];
            $this->projectResults[$projectId] = $row;
        }
        $resultsHtml = [];
        if(isset($this->projectResults)) {
            foreach($this->projectResults as $project) {
                $projectPath = Poggit::getRootPath() . "ci/$project->repoOwner/$project->repoName/~";
                $truncatedName = htmlspecialchars(substr($project->projectName, 0, 14) . (strlen($project->projectName) > 14 ? "..." : ""));
                $resultsHtml[] = <<<EOS
<div class="search-info">
    <p class="recentbuildbox">
        <a href="$projectPath">$truncatedName</a>
        <span class="remark">
            {$project->repoName} by {$project->repoOwner}<br />
            Type: {$project->projectType}
        </span>
     </p>
</div>
EOS;
            }
        }

        $html = '<div class="searchresultsheader"><h4>' . count($resultsHtml) . '  result' . (count($resultsHtml) != 1 ? "s" : "") . ' for "' . $_POST["search"] . '"</h4></div><div class="searchresultslist">';
        $html .= implode($resultsHtml);
        $html .= '</div>';
        echo json_encode([
            "html" => $html
        ]);
    }

    public function getName(): string {
        return "search.ajax";
    }

    protected function needLogin(): bool {
        return false;
    }
}
