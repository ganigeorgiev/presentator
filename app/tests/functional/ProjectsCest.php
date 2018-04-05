<?php
namespace app\tests\functional;

use Yii;
use app\tests\FunctionalTester;
use common\tests\fixtures\UserFixture;
use common\tests\fixtures\ProjectFixture;
use common\tests\fixtures\VersionFixture;
use common\tests\fixtures\ScreenFixture;
use common\tests\fixtures\ProjectPreviewFixture;
use common\tests\fixtures\ScreenCommentFixture;
use common\tests\fixtures\UserProjectRelFixture;
use common\models\User;
use common\models\Project;

/**
 * ProjectsController functional tests.
 *
 * @author Gani Georgiev <gani.georgiev@gmail.com>
 */
class ProjectsCest
{
    /**
     * @inheritdoc
     */
    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            'user' => [
                'class'    => UserFixture::className(),
                'dataFile' => Yii::getAlias('@common/tests/_data/user.php'),
            ],
            'project' => [
                'class'    => ProjectFixture::className(),
                'dataFile' => Yii::getAlias('@common/tests/_data/project.php'),
            ],
            'version' => [
                'class'    => VersionFixture::className(),
                'dataFile' => Yii::getAlias('@common/tests/_data/version.php'),
            ],
            'screen' => [
                'class'    => ScreenFixture::className(),
                'dataFile' => Yii::getAlias('@common/tests/_data/screen.php'),
            ],
            'projectPreview' => [
                'class'    => ProjectPreviewFixture::className(),
                'dataFile' => Yii::getAlias('@common/tests/_data/project_preview.php'),
            ],
            'userProjectRel' => [
                'class'    => UserProjectRelFixture::className(),
                'dataFile' => Yii::getAlias('@common/tests/_data/user_project_rel.php'),
            ],
        ]);
    }

    /* ===============================================================
     * `ProjectsController::actionIndex()` tests
     * ============================================================ */
    /**
     * @param FunctionalTester $I
     */
    public function indexPage(FunctionalTester $I)
    {
        $I->wantTo('Check if projects index page is rendered correctly');
        $I->cantAccessAsGuest(['projects/index']);
        $I->amLoggedInAs(1002);
        $I->amOnPage(['projects/index']);
        $I->seeResponseCodeIs(200);
        $I->seeCurrentUrlEquals(['projects/index']);
        $I->see('Projects');
    }

    /**
     * @param FunctionalTester $I
     */
    public function indexCreateProjectFail(FunctionalTester $I)
    {
        $I->wantTo('Fail creating a new project');
        $I->cantAccessAsGuest(['projects/index']);
        $I->amLoggedInAs(1002);
        $I->amOnPage(['projects/index']);
        $I->seeResponseCodeIs(200);
        $I->submitForm('#project_create_form', [
            'ProjectForm' => [
                'title'               => '',
                'type'                => Project::TYPE_TABLET,
                'subtype'             => 123,
                'isPasswordProtected' => 1,
                'password'            => '',
            ],
        ]);
        $I->seeElement('.field-projectform-title.has-error');
        $I->seeElement('.field-projectform-password.has-error');
        $I->seeElement('.field-projectform-subtype.has-error');
        $I->dontSeeElement('.field-projectform-type.has-error');
        $I->dontSeeElement('.field-projectform-ispasswordprotected.has-error');
        $I->seeCurrentUrlEquals(['projects/index']);
    }

    /**
     * @param FunctionalTester $I
     */
    public function indexCreateProjectSuccess(FunctionalTester $I)
    {
        $I->wantTo('Fail creating a new project');
        $I->cantAccessAsGuest(['projects/index']);
        $I->amLoggedInAs(1002);
        $I->amOnPage(['projects/index']);
        $I->seeResponseCodeIs(200);
        $I->submitForm('#project_create_form', [
            'ProjectForm' => [
                'title'               => 'My new test project title',
                'type'                => Project::TYPE_TABLET,
                'subtype'             => 21,
                'isPasswordProtected' => 1,
                'password'            => '123456',
            ],
        ]);
        $I->dontSeeElement('#project_create_form .has-error');

        $createdProject = $I->grabRecord(Project::className(), ['title' => 'My new test project title']);
        $I->seeCurrentUrlEquals(['projects/view', 'id' => $createdProject->id]);
    }

    /* ===============================================================
     * `ProjectsController::actionView()` tests
     * ============================================================ */
    /**
     * @param FunctionalTester $I
     */
    public function viewFail(FunctionalTester $I)
    {
        $I->wantTo('Try to view project not owned by the logged user');
        $I->cantAccessAsGuest(['projects/view', 'id' => 1004]);
        $I->amLoggedInAs(1002);
        $I->amOnPage(['projects/view', 'id' => 1004]);
        $I->seeResponseCodeIs(404);
    }

    /**
     * @param FunctionalTester $I
     */
    public function viewSuccess(FunctionalTester $I)
    {
        $I->wantTo('View project owned by the logged user');

        $scenarios = [
            1002 => 1001, // regular user
            1006 => 1002, // super user
        ];

        foreach ($scenarios as $userId => $projectId) {
            $project = Project::findOne($projectId);

            $I->cantAccessAsGuest(['projects/view', 'id' => $project->id]);
            $I->amLoggedInAs($userId);
            $I->amOnPage(['projects/view', 'id' => $project->id]);
            $I->seeResponseCodeIs(200);
            $I->see($project->title);
            $I->seeElement('#versions_list');
            $I->seeElement('#version_screens_tabs');
            $I->seeElement('#screens_bulk_panel');
            $I->seeElement('#screens_upload_popup');
            $I->seeElement('#screens_edit_popup');
            $I->seeElement('#project_preview_share_form');
            $I->seeElement('#admins_popup');
            $I->seeElement('#links_popup');
        }
    }

    /* ===============================================================
     * `ProjectsController::actionDelete()` tests
     * ============================================================ */
    /**
     * @param FunctionalTester $I
     */
    public function deleteFail(FunctionalTester $I)
    {
        $I->wantTo('Try to delete project not owned by the logged user');
        $I->cantAccessAsGuest(['projects/delete', 'id' => 1004]);
        $I->amLoggedInAs(1002);
        $I->sendPOST(['projects/delete', 'id' => 1004]);
        $I->seeResponseCodeIs(404);
        $I->dontSeeFlash('success');
    }

    /**
     * @param FunctionalTester $I
     */
    public function deleteSuccess(FunctionalTester $I)
    {
        $I->wantTo('Delete project owned by the logged user');

        $scenarios = [
            1002 => 1001, // regular user
            1006 => 1002, // super user
        ];

        foreach ($scenarios as $userId => $projectId) {
            $I->cantAccessAsGuest(['projects/delete', 'id' => $projectId]);
            $I->amLoggedInAs($userId);
            $I->sendPOST(['projects/delete', 'id' => $projectId]);
            $I->seeResponseCodeIs(200);
            $I->seeCurrentUrlEquals(['projects/index']);
            $I->seeFlash('success');
        }
    }

    /* ===============================================================
     * `ProjectsController::actionAjaxGetUpdateForm()` tests
     * ============================================================ */
    /**
     * @param FunctionalTester $I
     */
    public function ajaxGetUpdateFormFail(FunctionalTester $I)
    {
        $I->wantTo('Falsely fetch project update form');
        $I->amLoggedInAs(1002);
        $I->ensureAjaxGetActionAccess(['projects/ajax-get-update-form', 'id' => 1001]);

        $I->amGoingTo('try with a project that is not owned by the logged user');
        $I->sendAjaxGetRequest(['projects/ajax-get-update-form', 'id' => 1004]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"message":');
    }

    /**
     * @param FunctionalTester $I
     */
    public function ajaxGetUpdateFormSuccess(FunctionalTester $I)
    {
        $I->wantTo('Successfully fetch project update form');

        $scenarios = [
            1002 => 1001, // regular user
            1006 => 1001, // super user
        ];

        foreach ($scenarios as $userId => $projectId) {
            $I->amLoggedInAs($userId);
            $I->ensureAjaxGetActionAccess(['projects/ajax-get-update-form', 'id' => $projectId]);
            $I->sendAjaxGetRequest(['projects/ajax-get-update-form', 'id' => $projectId]);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContains('"success":true');
            $I->seeResponseContains('"updateForm":');
        }
    }

    /* ===============================================================
     * `ProjectsController::actionAjaxSaveUpdateForm()` tests
     * ============================================================ */
    /**
     * @param FunctionalTester $I
     */
    public function ajaxSaveUpdateFormFail(FunctionalTester $I)
    {
        $I->wantTo('Falsely update a project model');
        $I->amLoggedInAs(1002);
        $I->ensureAjaxPostActionAccess(['projects/ajax-save-update-form', 'id' => 1001]);

        $I->amGoingTo('try with a project that is not owned by the logged user');
        $I->sendAjaxPostRequest(['projects/ajax-save-update-form', 'id' => 1004], []);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"message":');

        $I->amGoingTo('try with a invalid project form data');
        $I->sendAjaxPostRequest(['projects/ajax-save-update-form', 'id' => 1001], [
            'ProjectForm' => [
                'title'               => '',
                'type'                => Project::TYPE_TABLET,
                'subtype'             => 123,
                'isPasswordProtected' => 1,
                'password'            => '',
                'autoScale'           => 'invalid_value',
                'retinaScale'         => 'invalid_value',
            ],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"message":');
    }

    /**
     * @param FunctionalTester $I
     */
    public function ajaxSaveUpdateFormSuccess(FunctionalTester $I)
    {
        $I->wantTo('Successfully update a project model');

        $scenarios = [
            1002 => 1001, // regular user
            1006 => 1001, // super user
        ];

        foreach ($scenarios as $userId => $projectId) {
            $I->amLoggedInAs($userId);
            $I->ensureAjaxPostActionAccess(['projects/ajax-save-update-form', 'id' => $projectId]);
            $I->sendAjaxPostRequest(['projects/ajax-save-update-form', 'id' => $projectId], [
                'ProjectForm' => [
                    'title'               => 'New title',
                    'type'                => Project::TYPE_TABLET,
                    'subtype'             => 21,
                    'isPasswordProtected' => 1,
                    'password'            => '123456',
                    'autoScale'           => false,
                    'retinaScale'         => false,
                ],
            ]);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContains('"success":true');
            $I->seeResponseContains('"message":');
            $I->seeResponseContains('"project":');
        }
    }

    /* ===============================================================
     * `ProjectsController::actionAjaxSearchProjects()` tests
     * ============================================================ */
    /**
     * @param FunctionalTester $I
     */
    public function ajaxSearchProjectsFail(FunctionalTester $I)
    {
        $I->wantTo('Falsely fetch search projects list');
        $I->amLoggedInAs(1002);
        $I->ensureAjaxGetActionAccess(['projects/ajax-search-projects', 'search' => '']);
        $I->sendAjaxGetRequest(['projects/ajax-search-projects'], [
            'search' => 'a', // search string must be atleast 2+ chars
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
    }

    /**
     * @param FunctionalTester $I
     */
    public function ajaxSearchProjectsSuccess(FunctionalTester $I)
    {
        $I->wantTo('Successfully fetch search projects list');

        $scenarios = [
            1002 => 'Lorem', // regular user
            1006 => 'Lorem', // super user
        ];

        foreach ($scenarios as $userId => $search) {
            $I->amLoggedInAs($userId);
            $I->ensureAjaxGetActionAccess(['projects/ajax-search-projects', 'search' => '']);
            $I->sendAjaxGetRequest(['projects/ajax-search-projects'], [
                'search' => $search,
            ]);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContains('"success":true');
            $I->seeResponseContains('"projectsHtml":');
        }
    }

    /* ===============================================================
     * `ProjectsController::actionAjaxLoadMore()` tests
     * ============================================================ */
    /**
     * @param FunctionalTester $I
     */
    public function ajaxLoadMore(FunctionalTester $I)
    {
        $I->wantTo('Loads more projects via ajax');
        $I->amLoggedInAs(1002);
        $I->ensureAjaxGetActionAccess(['projects/ajax-load-more'], ['page' => 1]);
        $I->sendAjaxGetRequest(['projects/ajax-load-more'], ['page' => 1]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":true');
        $I->seeResponseContains('"projectsHtml":');
        $I->seeResponseContains('"hasMoreProjects":');
    }

    /**
     * @param FunctionalTester $I
     */
    public function ajaxLoadMoreAsSuperUser(FunctionalTester $I)
    {
        $I->wantTo('Loads more projects via ajax as super user');
        $I->amLoggedInAs(1006);
        $I->ensureAjaxGetActionAccess(['projects/ajax-load-more'], ['page' => 1, 'mustBeOwner' => false]);
        $I->sendAjaxGetRequest(['projects/ajax-load-more'], ['page' => 1, 'mustBeOwner' => false]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":true');
        $I->seeResponseContains('"projectsHtml":');
        $I->seeResponseContains('"hasMoreProjects":');

        foreach (Project::find()->all() as $project) {
            $I->seeResponseContains('data-project-id=\"' . $project->id . '\"');
        }
    }

    /* ===============================================================
     * `ProjectsController::actionAjaxShare()` tests
     * ============================================================ */
    /**
     * @param FunctionalTester $I
     */
    public function ajaxShareFail(FunctionalTester $I)
    {
        $I->wantTo('Falsely share a project link');
        $I->amLoggedInAs(1002);
        $I->ensureAjaxPostActionAccess(['projects/ajax-share', 'id' => 1001]);

        $I->amGoingTo('try to share a project that is not owned by the logged user');
        $I->sendAjaxPostRequest(['projects/ajax-share', 'id' => 1003], [
            'ProjectShareForm' => [
                'email' => 'test@presentator.io',
            ],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"message":');
        $I->dontSeeEmailIsSent();

        $I->amGoingTo('try to submit wrong share form data');
        $I->sendAjaxPostRequest(['projects/ajax-share', 'id' => 1001], [
            'ProjectShareForm' => [
                'email' => '',
            ],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"errors":');
        $I->seeResponseContains('"email":');
        $I->dontSeeEmailIsSent();
    }

    /**
     * @param FunctionalTester $I
     */
    public function ajaxShareSuccess(FunctionalTester $I)
    {
        $I->wantTo('Successfully share a project link');

        $scenarios = [
            1002 => 1001, // regular user
            1006 => 1001, // super user
        ];

        foreach ($scenarios as $userId => $projectId) {
            $I->amLoggedInAs($userId);
            $I->ensureAjaxPostActionAccess(['projects/ajax-share', 'id' => $projectId]);
            $I->sendAjaxPostRequest(['projects/ajax-share', 'id' => $projectId], [
                'ProjectShareForm' => [
                    'email' => 'test@presentator.io',
                ],
            ]);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContains('"success":true');
            $I->seeResponseContains('"message":');
            $I->seeEmailIsSent();
            $message = $I->grabLastSentEmail();
            $I->assertArrayHasKey('test@presentator.io', $message->getTo());
        }
    }

    /* ===============================================================
     * `ProjectsController::actionAjaxRemoveAdmin()` tests
     * ============================================================ */
    /**
     * @param FunctionalTester $I
     */
    public function ajaxRemoveAdminFail(FunctionalTester $I)
    {
        $I->wantTo('Falsely remove/unlink a project admin');
        $I->amLoggedInAs(1002);
        $I->ensureAjaxPostActionAccess(['projects/ajax-remove-admin']);

        $I->amGoingTo('try to unlink user from project that is not owned by the logged user');
        $I->sendAjaxPostRequest(['projects/ajax-remove-admin'], [
            'projectId' => 1003,
            'userId'    => 1234, // doesn't matter
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"message":');
        $I->dontSeeEmailIsSent();

        $I->amGoingTo('try to unlink a not linked project admin');
        $I->sendAjaxPostRequest(['projects/ajax-remove-admin'], [
            'projectId' => 1001,
            'userId'    => 1004,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"message":');
        $I->dontSeeEmailIsSent();

        $I->amGoingTo('try to unlink an user from a project with only one admin');
        $I->sendAjaxPostRequest(['projects/ajax-remove-admin'], [
            'projectId' => 1001,
            'userId'    => 1002,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"message":');
        $I->dontSeeEmailIsSent();
    }

    /**
     * @param FunctionalTester $I
     */
    public function ajaxRemoveAdminSuccess(FunctionalTester $I)
    {
        $I->wantTo('Successfully unlink a project admin');

        $I->amGoingTo('try to unlink the current logged user');
        $I->amLoggedInAs(1003);
        $I->ensureAjaxPostActionAccess(['projects/ajax-remove-admin']);
        $I->sendAjaxPostRequest(['projects/ajax-remove-admin'], [
            'projectId' => 1002,
            'userId'    => 1003,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":true');
        $I->seeResponseContains('"message":');
        $I->dontSeeEmailIsSent();

        $I->amGoingTo('try to unlink a project admin');
        $I->amLoggedInAs(1003);
        $unlinkedUser = User::findOne(1006);
        $I->ensureAjaxPostActionAccess(['projects/ajax-remove-admin']);
        $I->sendAjaxPostRequest(['projects/ajax-remove-admin'], [
            'projectId' => 1004,
            'userId'    => $unlinkedUser->id,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":true');
        $I->seeResponseContains('"message":');
        $I->seeEmailIsSent();
        $message = $I->grabLastSentEmail();
        $I->assertArrayHasKey($unlinkedUser->email, $message->getTo());
    }

    /**
     * @param FunctionalTester $I
     */
    public function ajaxRemoveAdminAsSuperUser(FunctionalTester $I)
    {
        $I->wantTo('Successfully unlink a project admin as super user');
        $I->amLoggedInAs(1006);

        $I->amGoingTo('try to unlink the current logged in user from a project owned by the super user');
        $I->ensureAjaxPostActionAccess(['projects/ajax-remove-admin']);
        $I->sendAjaxPostRequest(['projects/ajax-remove-admin'], [
            'projectId' => 1004,
            'userId'    => 1006,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":true');
        $I->seeResponseContains('"message":');
        $I->dontSeeEmailIsSent();

        $I->amGoingTo('try to unlink a project admin from a project not owned by the super user');
        $unlinkedUser = User::findOne(1003);
        $I->ensureAjaxPostActionAccess(['projects/ajax-remove-admin']);
        $I->sendAjaxPostRequest(['projects/ajax-remove-admin'], [
            'projectId' => 1002,
            'userId'    => $unlinkedUser->id,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":true');
        $I->seeResponseContains('"message":');
        $I->seeEmailIsSent();
        $message = $I->grabLastSentEmail();
        $I->assertArrayHasKey($unlinkedUser->email, $message->getTo());
    }

    /* ===============================================================
     * `ProjectsController::actionAjaxAddAdmin()` tests
     * ============================================================ */
    /**
     * @param FunctionalTester $I
     */
    public function ajaxAddAdminFail(FunctionalTester $I)
    {
        $I->wantTo('Falsely link a project admin');
        $I->amLoggedInAs(1002);
        $I->ensureAjaxPostActionAccess(['projects/ajax-add-admin']);

        $I->amGoingTo('try to link an user from a project that is not owned by the logged one');
        $I->sendAjaxPostRequest(['projects/ajax-add-admin'], [
            'projectId' => 1003,
            'userId'    => 1003,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"message":');
        $I->dontSeeEmailIsSent();

        $I->amGoingTo('try to link a missing user');
        $I->sendAjaxPostRequest(['projects/ajax-add-admin'], [
            'projectId' => 1001,
            'userId'    => 12345, // not existing user
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"message":');
        $I->dontSeeEmailIsSent();
    }

    /**
     * @param FunctionalTester $I
     */
    public function ajaxAddAdminSuccess(FunctionalTester $I)
    {
        $I->wantTo('Successfully link a project admin');

        $scenarios = [
            1002 => 1001, // regular user
            1006 => 1001, // super user
        ];

        $linkedUser = User::findOne(1003);

        foreach ($scenarios as $userId => $projectId) {
            $I->amLoggedInAs($userId);
            $I->ensureAjaxPostActionAccess(['projects/ajax-add-admin']);
            $I->sendAjaxPostRequest(['projects/ajax-add-admin'], [
                'projectId' => $projectId,
                'userId'    => $linkedUser->id,
            ]);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContains('"success":true');
            $I->seeResponseContains('"listItemHtml":');
            $I->seeResponseContains('"message":');
            $I->seeEmailIsSent();
            $I->seeEmailIsSent();
            $message = $I->grabLastSentEmail();
            $I->assertArrayHasKey($linkedUser->email, $message->getTo());
        }
    }

    /* ===============================================================
     * `ProjectsController::actionAjaxSearchUsers()` tests
     * ============================================================ */
    /**
     * @param FunctionalTester $I
     */
    public function ajaxSearchUsersFail(FunctionalTester $I)
    {
        $I->wantTo('Falsely search for a new users');
        $I->amLoggedInAs(1002);
        $I->ensureAjaxGetActionAccess(['projects/ajax-search-users'], ['id' => 1001, 'search' => 'John']);

        $I->amGoingTo('try to search for users for a project that is not owned by the logged user');
        $I->sendAjaxGetRequest(['projects/ajax-search-users'], ['id' => 1003, 'search' => 'John']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"message":');

        $I->amGoingTo('try to search for users with invalid search term length');
        $I->sendAjaxGetRequest(['projects/ajax-search-users'], ['id' => 1001, 'search' => 'a']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('"success":false');
        $I->seeResponseContains('"message":');
    }

    /**
     * @param FunctionalTester $I
     */
    public function ajaxSearchUsersSuccess(FunctionalTester $I)
    {
        $scenarios = [
            1002 => 1001, // regular user
            1006 => 1001, // super user
        ];

        $I->wantTo('Successfully search for a new users');

        foreach ($scenarios as $userId => $projectId) {
            $I->amLoggedInAs($userId);
            $I->ensureAjaxGetActionAccess(['projects/ajax-search-users'], ['id' => $projectId, 'search' => 'John']);
            $I->sendAjaxGetRequest(['projects/ajax-search-users'], ['id' => $projectId, 'search' => 'John']);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContains('"success":true');
            $I->seeResponseContains('"suggestionsHtml":');
        }
    }
}
