<?php
use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use common\models\User;

/**
 * @var $this            \yii\web\View
 * @var $projectForm     \app\models\ProjectForm
 * @var $typesList       array
 * @var $subtypesList    array
 * @var $hasMoreProjects boolean
 * @var $commentCounters array
 */

$this->title = Yii::t('app', 'Projects');

$user = Yii::$app->user->identity;
?>

<?php $this->beginBlock('page_title'); ?>
    <h3 class="page-title"><?= Html::encode($this->title) ?></h3>
    <button type="button" data-popup="#project_create_popup" class="btn btn-xs btn-success m-t-5"><?= Yii::t('app', 'Create new project') ?></button>
<?php $this->endBlock(); ?>


<div class="filter-block">
    <?php if ($user->type == User::TYPE_SUPER): ?>
        <div class="filter-item min-width">
            <div class="form-group">
                <input type="checkbox" id="only_my_projects_toggle">
                <label for="only_my_projects_toggle"><?= Yii::t('app', 'Show only my projects') ?></label>
            </div>
        </div>
    <?php endif; ?>

    <div class="filter-item max-width">
        <div id="projects_search_bar" class="search-bar">
            <label id="projects_search_handle" class="search-icon" for="projects_search_input"><i class="ion ion-ios-search"></i></label>
            <span id="projects_search_clear" class="search-clear clear-projects-search"><i class="ion ion-backspace"></i></span>
            <input type="text" id="projects_search_input" class="search-input" placeholder="<?= Yii::t('app', 'Search for projects...') ?>">
        </div>
    </div>
</div>


<div id="projects_search_list_wrapper" style="display: none;">
    <h5>
        <span class="hint"><?= Yii::t('app', 'Search results') ?>:</span>
    </h5>
    <div id="no_search_results" style="display: none;">
        <h6>
            <span><?= Yii::t('app', 'No results found') ?></span>
            <button type="button" class="btn btn-label btn-danger clear-projects-search"><?= Yii::t('app', 'Back to all projects') ?></button>
        </h6>
    </div>
    <div id="projects_search_list" class="projects-list">
    </div>
</div>

<div class="clearfix"></div>

<div id="projects_list_wrapper" class="block">
    <div id="projects_list" class="projects-list">
        <div class="box action-box primary" data-popup="#project_create_popup">
            <div class="content">
                <div class="table-wrapper">
                    <div class="table-cell">
                        <span class="icon"><i class="ion ion-ios-plus-outline"></i></span>
                        <span class="txt"><?= Yii::t('app', 'Create new project') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="block text-center m-t-30 m-b-30">
        <h4 id="no_more_projects" class="hint" style="display: none;">
            <?= Yii::t('app', 'All projects are loaded') ?>
        </h4>

        <button type="button" id="load_more_projects" class="btn btn-primary btn-lg btn-cons" style="display: none;">
            <?= Yii::t('app', 'Load more projects') ?>
        </button>
    </div>
</div>

<div id="project_create_popup" class="popup">
    <div class="popup-content">
        <h3 class="popup-title"><?= Yii::t('app', 'Create new project') ?></h3>
        <span class="popup-close close-icon"></span>
        <div class="content">
            <?= $this->render('_form', [
                'model'        => $projectForm,
                'typesList'    => $typesList,
                'subtypesList' => $subtypesList,
            ]); ?>
        </div>
    </div>
</div>

<?php
$this->registerJsFile('/js/project-index.view.js?v=1507457981');
$this->registerJs('
    var projectIndex = new ProjectIndex({
        ajaxLoadProjectsUrl:   "' . Url::to(['projects/ajax-load-more']) . '",
        ajaxSearchProjectsUrl: "' . Url::to(['projects/ajax-search-projects']) . '"
    });
', View::POS_READY, 'projects-index-js');
