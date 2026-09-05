@extends('layouts.base')

@section('css')
<link rel='stylesheet' href='/css/admin.css'>
<link rel='stylesheet' href='/css/datatables.min.css'>
@endsection

@section('content')
<div ng-controller="AdminCtrl" class="ng-root">
    <div class='col-md-2'>
        <ul class='nav nav-pills nav-stacked admin-nav' role='tablist'>
            <li role='presentation' aria-controls="home" class='admin-nav-item active'><a href='#home'>Home</a></li>
            <li role='presentation' aria-controls="links" class='admin-nav-item'><a href='#links'>Links</a></li>
            <li role='presentation' aria-controls="settings" class='admin-nav-item'><a href='#settings'>Settings</a></li>

            @if ($role == $admin_role)
            <li role='presentation' class='admin-nav-item'><a href='#admin'>Admin</a></li>
            @endif

            @if ($api_active == 1)
            <li role='presentation' class='admin-nav-item'><a href='#developer'>Developer</a></li>
            @endif
        </ul>
    </div>
    <div class='col-md-10'>
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="home">
                <h2>Welcome to your {{env('APP_NAME')}} dashboard!</h2>
                <p>Use the links on the left hand side to navigate your {{env('APP_NAME')}} dashboard.</p>
            </div>

            <div role="tabpanel" class="tab-pane" id="links">
                @include('snippets.link_table', [
                    'table_id' => 'user_links_table'
                ])
            </div>

            <div role="tabpanel" class="tab-pane" id="settings">
                <h3>Change Password</h3>
                <form action='/admin/action/change_password' method='POST'>
                    Old Password: <input class="form-control password-box" type='password' name='current_password' />
                    New Password: <input class="form-control password-box" type='password' name='new_password' />
                    <input type="hidden" name='_token' value='{{csrf_token()}}' />
                    <input type='submit' class='btn btn-success change-password-btn'/>
                </form>
            </div>

            @if ($role == $admin_role)
            <div role="tabpanel" class="tab-pane" id="admin">
                <h3>Links</h3>
                @include('snippets.link_table', [
                    'table_id' => 'admin_links_table'
                ])

                <div class="housekeeping-section" style="margin-top: 25px; margin-bottom: 25px;">
                    <h3>Link Housekeeping & Spam Cleanup</h3>
                    <p class="text-muted">Search, preview, and bulk disable or delete spam and ambiguous destination URLs.</p>
                    <a ng-click="state.showHousekeepingWell = !state.showHousekeepingWell" class="btn btn-warning btn-sm status-display">
                        <span ng-if="!state.showHousekeepingWell">Open Spam Cleaner</span><span ng-if="state.showHousekeepingWell">Close Cleaner</span>
                    </a>

                    <div ng-if="state.showHousekeepingWell" class="well" style="margin-top: 15px;">
                        <form class="form-horizontal">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Domain / Keyword:</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" ng-model="housekeeping.pattern" placeholder="e.g. spam-site.com, *.xyz, or telegram.me">
                                </div>
                                <div class="col-sm-4">
                                    <select class="form-control" ng-model="housekeeping.matchType">
                                        <option value="domain">Domain / Host Match</option>
                                        <option value="contains">URL Contains Substring</option>
                                        <option value="exact">Exact URL Match</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Scope Filter:</label>
                                <div class="col-sm-4">
                                    <select class="form-control" ng-model="housekeeping.scope">
                                        <option value="all">All Links</option>
                                        <option value="anon">Anonymous Links Only</option>
                                    </select>
                                </div>
                                <label class="col-sm-2 control-label">Action:</label>
                                <div class="col-sm-4">
                                    <select class="form-control" ng-model="housekeeping.actionType">
                                        <option value="disable">Disable Links (Safe - Blocks Traffic)</option>
                                        <option value="delete">Permanently Delete Links & Stats</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <button type="button" class="btn btn-info" ng-click="previewHousekeeping()" ng-disabled="!housekeeping.pattern || housekeeping.loading">
                                        Preview Matching Links
                                    </button>
                                    <button type="button" class="btn btn-danger" ng-click="executeHousekeeping()" ng-disabled="!housekeeping.pattern || housekeeping.loading" style="margin-left: 10px;">
                                        Run Cleanup
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div ng-if="housekeeping.previewLoaded" style="margin-top: 15px;">
                            <hr>
                            <h4>Matches Found: <span class="label label-danger">@{{ housekeeping.previewCount }}</span></h4>
                            <div ng-if="housekeeping.previewCount > 0" class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-bordered table-striped table-condensed">
                                    <thead>
                                        <tr>
                                            <th>Short URL</th>
                                            <th>Long URL</th>
                                            <th>Creator</th>
                                            <th>Clicks</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr ng-repeat="item in housekeeping.previewSample">
                                            <td><a href="/@{{ item.short_url }}" target="_blank">@{{ item.short_url }}</a></td>
                                            <td class="wrap-text" style="word-break: break-all; max-width: 300px;">@{{ item.long_url }}</td>
                                            <td>@{{ item.creator || 'Anonymous' }}</td>
                                            <td>@{{ item.clicks }}</td>
                                            <td>
                                                <span class="label" ng-class="{'label-danger': item.is_disabled, 'label-success': !item.is_disabled}">
                                                    @{{ item.is_disabled ? 'Disabled' : 'Active' }}
                                                </span>
                                            </td>
                                            <td>@{{ item.created_at }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="users-heading">Users</h3>
                <a ng-click="state.showNewUserWell = !state.showNewUserWell" class="btn btn-primary btn-sm status-display">New</a>

                <div ng-if="state.showNewUserWell" class="new-user-fields well">
                    <table class="table">
                        <tr>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th></th>
                        </tr>
                        <tr id="new-user-form">
                            <td><input type="text" class="form-control" ng-model="newUserParams.username"></td>
                            <td><input type="password" class="form-control" ng-model="newUserParams.userPassword"></td>
                            <td><input type="email" class="form-control" ng-model="newUserParams.userEmail"></td>
                            <td>
                                <select class="form-control new-user-role" ng-model="newUserParams.userRole">
                                    @foreach  ($user_roles as $role_text => $role_val)
                                        <option value="{{$role_val}}">{{$role_text}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <a ng-click="addNewUser($event)" class="btn btn-primary btn-sm status-display new-user-add">Add</a>
                            </td>
                        </tr>
                    </table>
                </div>

                @include('snippets.user_table', [
                    'table_id' => 'admin_users_table'
                ])

            </div>
            @endif

            @if ($api_active == 1)
            <div role="tabpanel" class="tab-pane" id="developer">
                <h3>Developer</h3>

                <p>API keys and documentation for developers.</p>
                <p>
                    Documentation:
                    <a href='http://docs.polr.me/en/latest/developer-guide/api/'>http://docs.polr.me/en/latest/developer-guide/api/</a>
                </p>

                <h4>API Key: </h4>
                <div class='row'>
                    <div class='col-md-8'>
                        <input class='form-control status-display' disabled type='text' value='{{$api_key}}'>
                    </div>
                    <div class='col-md-4'>
                        <a href='#' ng-click="generateNewAPIKey($event, '{{$user_id}}', true)" id='api-reset-key' class='btn btn-danger'>Reset</a>
                    </div>
                </div>


                <h4>API Quota: </h4>
                <h2 class='api-quota'>
                    @if ($api_quota == -1)
                        unlimited
                    @else
                        <code>{{$api_quota}}</code>
                    @endif
                </h2>
                <span> requests per minute</span>
            </div>
            @endif
        </div>
    </div>

    <div class="angular-modals">
        <edit-long-link-modal ng-repeat="modal in modals.editLongLink" link-ending="modal.linkEnding"
            old-long-link="modal.oldLongLink" clean-modals="cleanModals"></edit-long-link-modal>
        <edit-user-api-info-modal ng-repeat="modal in modals.editUserApiInfo" user-id="modal.userId"
            api-quota="modal.apiQuota" api-active="modal.apiActive" api-key="modal.apiKey"
            generate-new-api-key="generateNewAPIKey" clean-modals="cleanModals"></edit-user-api-info>
    </div>
</div>


@endsection

@section('js')
{{-- Include modal templates --}}
@include('snippets.modals')

{{-- Include extra JS --}}
<script src='/js/datatables.min.js'></script>
<script src='/js/api.js'></script>
<script src='/js/AdminCtrl.js'></script>
@endsection
