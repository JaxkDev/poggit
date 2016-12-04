<?php

/*
 * Poggit
 *
 * Copyright (C) 2016 Poggit
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

namespace poggit\module\releases\submit;

use poggit\model\PluginRelease;
use poggit\module\VarPage;
use poggit\Poggit;

class RealSubmitPage extends VarPage {
    /** @var SubmitPluginModule */
    private $module;
    private $mainAction;

    public function __construct(SubmitPluginModule $module) {
        $this->module = $module;
        $this->mainAction = ($this->module->lastRelease !== []) ? "Releasing update" : "Releasing plugin";
    }

    public function getTitle(): string {
        return $this->mainAction . ": " . $this->module->owner . "/" . $this->module->repo . "/" . $this->module->project;
    }

    public function output() {
        $buildPath = Poggit::getRootPath() . "ci/{$this->module->owner}/{$this->module->repo}/{$this->module->project}/dev:{$this->module->build}";
        ?>
        <script>
            var pluginSubmitData = {
                owner: <?= json_encode($this->module->owner, JSON_UNESCAPED_SLASHES) ?>,
                repo: <?= json_encode($this->module->repo, JSON_UNESCAPED_SLASHES) ?>,
                project: <?= json_encode($this->module->project, JSON_UNESCAPED_SLASHES) ?>,
                build: <?= json_encode($this->module->build, JSON_UNESCAPED_SLASHES) ?>,
                projectDetails: <?= json_encode($this->module->projectDetails, JSON_UNESCAPED_SLASHES) ?>,
                lastRelease: <?= json_encode($this->module->lastRelease === [] ? null : $this->module->lastRelease, JSON_UNESCAPED_SLASHES) ?>
            };
        </script>
        <div class="realsubmitwrapper">
            <div class="submittitle"><h1><?= $this->getTitle() ?></h1></div>
            <p>Submitting build: <a href="<?= $buildPath ?>" target="_blank">Build #<?= $this->module->build ?></a></p>
            <div class="form-table">
                <div class="form-row">
                    <div class="form-key">Plugin name</div>
                    <div class="form-value">
                        <input id="submit-pluginName" onblur="checkPluginName();" type="text" size="32"
                               value="<?= $this->module->lastRelease["name"] ?? $this->module->project ?>"
                            <?= $this->module->lastRelease === [] ? "autofocus" : "disabled" ?>
                        />
                        <span class="explain" id="submit-afterPluginName" style="font-weight: bold;"></span>
                        <span class="explain">Name of the plugin to be displayed. This can be different from the
                                project name, and it must not already exist.</span></div>
                </div>
                <div class="form-row">
                    <div class="form-key">Tag line</div>
                    <div class="form-value">
                        <input type="text" size="64" maxlength="256" id="submit-shortDesc"
                               value="<?= $this->module->lastRelease["shortDesc"] ?? "" ?>"/><br/>
                        <span class="explain">One-line text describing the plugin</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-key">Version name</div>
                    <div class="form-value">
                        <input type="text" id="submit-version" size="10"/><br/>
                        <span class="explain">Unique version name of this plugin release</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-key">Plugin Description</div>
                    <div class="form-value">
                        <textarea name="pluginDesc" id="submit-pluginDescTextArea" cols="72"
                                  rows="10"></textarea><br/>
                        Format: <select id="submit-pluginDescTypeSelect">
                            <option value="md">GH Markdown (context:
                                github.com/<?= $this->module->owner ?>/<?= $this->module->repo ?>)
                            </option>
                            <option value="txt">Plain text</option>
                        </select><br/>
                        <div id="possibleDescriptionImports"></div>
                        <br/>
                        <span class="explain">Brief explanation of your plugin. You should include
                                <strong>all</strong> features provided by your plugin here so that reviewers won't be
                                confused by the code you write.</span>
                    </div>
                </div>
                <?php if($this->module->lastRelease !== []) { ?>
                    <div class="form-row">
                        <div class="form-key">What's new</div>
                        <div class="form-value">
                            <textarea id="submit-pluginChangeLogTextArea" cols="72"
                                      rows="10"></textarea><br/>
                            Format: <select id="submit-pluginChangeLogTypeSelect">
                                <option value="md">GH Markdown (context:
                                    github.com/<?= $this->module->owner ?>/<?= $this->module->repo ?>)
                                </option>
                                <option value="txt">Plain text</option>
                            </select><br/>
                            <span class="explain">Changelog for this update. Briefly point out what is new in this update.
                            This information is used by plugin reviewers.</span>
                        </div>
                    </div>
                <?php } ?>
                <!-- TODO inherit from previous release -->
                <div class="form-row">
                    <div class="form-key">License</div>
                    <div class="form-value">
                        <div class="explain">
                            <p>Choose a license to be displayed in the plugin page. The templates
                                provided by GitHub will be used. Poggit will not try to fetch the license from your
                                GitHub
                                repo. You may also put a custom license here.</p>
                            <p>Also note that Poggit is not a legal firm. Please do not rely on Poggit for legal
                                license
                                information.</p>
                        </div>
                        <select id="submit-chooseLicense">
                            <option value="nil" selected>No license</option>
                            <option value="custom">Custom license</option>
                        </select>
                        <span class="action disabled" id="viewLicenseDetails">View license details</span><br/>
                        <textarea id="submit-customLicense" style="display: none;"
                                  placeholder="Custom license content" rows="30"></textarea>
                    </div>
                </div>
                <!-- TODO inherit from previous release -->
                <div class="form-row">
                    <div class="form-key">Pre-release</div>
                    <div class="form-value">
                        <input type="checkbox" id="submit-isPreRelease"><br/>
                        <span class="explain">A pre-release is a preview of a release of your plugin. It must still
                                be functional even if some features are not completed, and you must emphasize this
                                in the description. Pre-releases can be a bit buggy or unstable, but not too much or they
                                will not be approved.
                        </span>
                    </div>
                </div>
                <!-- TODO inherit from previous release, and disable if inherited? -->
                <div class="form-row">
                    <div class="form-key">Categories</div>
                    <div class="form-value">
                        Major category: <select id="submit-majorCategory">
                            <?php
                            foreach(PluginRelease::$CATEGORIES as $id => $name) {
                                $selected = $id === 8 ? "selected" : "";
                                echo "<option value='$id' $selected>" . htmlspecialchars($name) . "</option>";
                            }
                            ?>
                        </select><br/>
                        Minor categories:
                        <div class="submitReleaseCats" class="submit-categories">
                            <?php
                            foreach(PluginRelease::$CATEGORIES as $id => $name) {
                                echo "<div class='cbinput'><input type='checkbox' value='$id'>" . htmlspecialchars($name) . "</input></div>";
                            }
                            ?>
                        </div>
                        <p class="explain">This plugin will be listed in the major category, but users subscribing
                            to
                            the minor categories will also be notified when this plugin is released.<br/>
                            You do not need to select the major category in minor categories</p>
                    </div>
                </div>
                <!-- TODO inherit from previous release -->
                <div class="form-row">
                    <div class="form-key">Keywords</div>
                    <div class="form-value">
                        <input type="text" id="submit-keywords">
                        <p class="explain">Separate different keywords with spaces. These keywords will be used to
                            let
                            users search plugins. Synonyms are allowed, but use no more than 25 keywords.</p>
                    </div>
                </div>
                <!-- TODO inherit from previous release -->
                <div class="form-row">
                    <div class="form-key">Supported API versions</div>
                    <div class="form-value">
                        <span class="explain">The PocketMine <?php Poggit::ghLink("https://github.com/pmmp/PocketMine-MP") ?>
                            <em>API versions</em> supported by this plugin.<br/>
                            Please note that Poggit only accepts submission of plugins written and tested on PocketMine.
                            Plugins written for spoons are <strong>not</strong> accepted.
                        </span>
                        <table class="info-table" id="supportedSpoonsValue">
                            <tr>
                                <th><em>API</em> Version</th>
                            </tr>
                            <tr id="baseSpoonForm" class="submit-spoonEntry" style="display: none;">
                                <td><input type="text" class="submit-spoonVersion"/></td>
                                <td><span class="action deleteSpoonRow" onclick="deleteRowFromListInfoTable(this);">X
                                    </span></td>
                            </tr>
                        </table>
                        <span onclick='addRowToListInfoTable("baseSpoonForm", "supportedSpoonsValue");'
                              class="action">Add row</span>
                    </div>
                </div>
                <!-- TODO inherit from previous release -->
                <div class="form-row">
                    <script>

                    </script>
                    <div class="form-key">Dependencies</div>
                    <div class="form-value">
                        <table class="info-table" id="dependenciesValue">
                            <tr>
                                <th>Plugin name</th>
                                <th>Compatible version</th>
                                <th>Relevant Poggit release</th>
                                <th>Required or optional?</th>
                            </tr>
                            <tr id="baseDepForm" class="submit-depEntry" style="display: none;">
                                <td><input type="text" class="submit-depName"/></td>
                                <td><input type="text" class="submit-depVersion"/></td>
                                <td><input type="button" class="submit-depRelIdTrigger"
                                           onclick='searchDep($(this).parents("tr"))'/>
                                    <span class="submit-depRelId" data-relId="0" data-projId="0"></span>
                                </td>
                                <td>
                                    <select class="submit-depSoftness">
                                        <option value="hard">Required</option>
                                        <option value="soft">Optional</option>
                                    </select>
                                </td>
                                <td><span class="action deleteDepRow"
                                          onclick="deleteRowFromListInfoTable(this)">X</span>
                                </td>
                            </tr>
                        </table>
                        <span onclick='addRowToListInfoTable("baseDepForm", "dependenciesValue");'
                              class="action">Add row</span>
                        <span class="explain">Other plugins that this plugin requires to run with, or optionally works
                            with. You are recommended to put the latest version that the other plugin has been tested
                            to work with, but you don't need to update this value if new compatible versions of the
                            other plugin are released.
                        </span>
                    </div>
                </div>
                <!-- TODO inherit from previous release -->
                <div class="form-row">
                    <div class="form-key">Permissions</div>
                    <div class="form-value">
                        <span class="explain">The actions on the server that this plugin does</span>
                        <div id="submit-perms" class="submit-perms-wrapper">
                            <?php foreach(PluginRelease::$PERMISSIONS as $value => list($perm, $reason)) { ?>
                                <div class="submit-perms-row">
                                    <div class="cbinput">
                                        <input type="checkbox" class="submit-permEntry" value="<?= $value ?>"/>
                                        <?= htmlspecialchars($perm) ?>
                                    </div>
                                    <div class="remark"><?= htmlspecialchars($reason) ?></div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-key">Requirements/<br/>Enhancements</div>
                    <div class="form-value">
                        <p class="explain">Requirements and Enhancements are external processes run on the server, or
                            some information different for every user that you cannot provide a default value for them
                            in the config file. In other words, they must be installed or setup manually when the user
                            installs the plugin.<br/>
                            For example, if your plugin uses mail, a mail server has to be installed first.<br/>
                            Another example is that if your plugin uses the external API of a website like the GitHub
                            API, your plugin would require the API token from the user. The user must manually enter
                            this information to the plugin after installing.<br/>
                            <strong>Requirements</strong> are <em>mandatory</em> for the plugin, i.e. if you don't set
                            the required values, the plugin won't work.<br/>
                            <strong>Enhancements</strong> are <em>optional</em>. The plugin can still start and work
                            properly without the values set, but some optional features won't be enabled.
                        </p>
                        <div id="submit-req">
                            <table class="info-table" id="reqrValue">
                                <tr>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Required?</th>
                                </tr>
                                <tr id="baseReqrForm" class="submit-reqrEntry" style="display: none;">
                                    <td>
                                        <select class="submit-reqrType">
                                            <option value="mail">Mail server (please specify type type of mail server
                                                required)
                                            </option>
                                            <option value="mysql">MySQL database</option>
                                            <option value="apiToken">Service API token (please specify what service)
                                            </option>
                                            <option value="password">Passwords for services provided by the plugin
                                            </option>
                                            <option value="other">Other (please specify)</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="submit-reqrSpec"/></td>
                                    <td>
                                        <select class="submit-reqrEnhc">
                                            <option value="requirement">Requirement</option>
                                            <option value="enhancement">Enhancement</option>
                                        </select>
                                    </td>
                                    <td><span class="action deleteReqrRow"
                                              onclick="deleteRowFromListInfoTable(this)">X</span>
                                    </td>
                                </tr>
                            </table>
                            <span onclick='addRowToListInfoTable("baseReqrForm", "reqrValue");'
                                  class="action">Add row</span>
                        </div>
                    </div>
                </div>

                <!-- TODO load icon from GitHub -->

                <p><span class="action">Submit plugin <?= $this->module->lastRelease === [] ? "" : "update" ?></span>
                </p>
            </div>
            <div id="previewLicenseDetailsDialog">
                <h5><a id="previewLicenseName" target="_blank"></a></h5>
                <p id="previewLicenseDesc"></p>
                <pre id="previewLicenseBody"></pre>
            </div>
        </div>
        <?php
    }

    public function includeMoreJs() {
        $this->module->includeJs("submit");
    }
}
