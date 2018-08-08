<?php
use yii\web\View;
use yii\helpers\Html;
use yii\helpers\Url;
use common\widgets\FlashAlert;
use common\widgets\LanguageSwitch;

/**
 * @var $this               \yii\web\View
 * @var $isLoginAttemp      boolean
 * @var $loginForm          \app\models\LoginForm
 * @var $registerForm       \app\models\RegisterForm
 * @var $hasFbConfig        boolean
 * @var $hasGoogleConfig    boolean
 * @var $hasGitlabConfig    boolean
 * @var $hasReCaptchaConfig boolean
 */

$this->title = Yii::t('app', 'Login');
?>

<?php $this->beginBlock('before_global_wrapper'); ?>
    <div class="diagonal-bg-wrapper"><span id="diagonal_bg" class="diagonal-bg" style="transform: translate3d(-50%, -50%, 0px) rotate(-42.6deg);"></span></div>
<?php $this->endBlock(); ?>

<div class="table-wrapper full-vh-height">
    <div class="table-cell text-center">
        <a href="<?= Url::to(['site/index']) ?>" class="logo">
            <img src="/images/logo_large_white.png" alt="Presentator logo">
            <div class="txt">Presentator</div>
        </a>
        <div class="clearfix"></div>
        <div id="auth_panel" class="auth-panel">
            <?php if (Yii::$app->session->hasFlash('registerSuccess')): ?>
                <div class="content padded text-center">
                    <h3><?= Yii::t('app', 'Successfully registered!') ?></h3>
                    <p><?= Yii::t('app', 'Check your email for further instructions how to activate your account.') ?></p>
                </div>
            <?php else: ?>
                <?= FlashAlert::widget(['close' => false, 'options' => ['class' => 'no-radius-b-l no-radius-b-r']]) ?>

                <div id="auth_tabs" class="tabs m-t-30 m-b-30">
                    <div class="tabs-header">
                        <div class="tab-item <?= $isLoginAttemp ? 'active' : '' ?>" data-target="#login"><span class="txt"><?= Yii::t('app', 'Login') ?></span></div>
                        <div class="tab-item <?= !$isLoginAttemp ? 'active' : '' ?>" data-target="#register"><span class="txt"><?= Yii::t('app', 'Register') ?></span></div>
                    </div>
                    <div class="tabs-content p-b-0">
                        <div id="login" class="tab-item <?= $isLoginAttemp ? 'active' : '' ?>">
                            <?= $this->render('_login_form', ['model' => $loginForm]); ?>
                        </div>
                        <div id="register" class="tab-item <?= !$isLoginAttemp ? 'active' : '' ?>">
                            <?= $this->render('_register_form', ['model' => $registerForm]); ?>
                        </div>
                    </div>
                </div>

                <?php if ($hasFbConfig || $hasGoogleConfig || $hasGitlabConfig): ?>
                    <footer class="footer m-t-0 text-center m-t-0">
                        <div class="auth-group">
                            <?php if ($hasFbConfig): ?>
                                <div class="auth-group-item">
                                    <a href="<?= Url::to(['site/auth', 'authclient' => 'facebook']) ?>"
                                        class="auth-group-link facebook-link"
                                        data-window="facebookLogin"
                                        data-width="990"
                                        data-height="700"
                                    >
                                        <i class="ion ion-logo-facebook"></i>
                                        <span class="txt"><?= Yii::t('app', 'Login with Facebook') ?></span>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if ($hasGoogleConfig): ?>
                                <div class="auth-group-item">
                                    <a href="<?= Url::to(['site/auth', 'authclient' => 'google']) ?>"
                                        class="auth-group-link google-link"
                                        data-window="googleLogin"
                                        data-width="990"
                                        data-height="700"
                                    >
                                        <i class="ion ion-logo-google"></i>
                                        <span class="txt"><?= Yii::t('app', 'Login with Google') ?></span>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if ($hasGitlabConfig): ?>
                                <div class="auth-group-item">
                                    <a href="<?= Url::to(['site/auth', 'authclient' => 'gitlab']) ?>"
                                        class="auth-group-link gitlab-link"
                                    >
                                        <img src="/images/gitlab_logo.svg" alt="Gitlab logo">
                                        <span class="txt"><?= Yii::t('app', 'Login with Gitlab') ?></span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </footer>
                <?php endif; ?>
            <?php endif ?>

            <?= LanguageSwitch::widget(); ?>
        </div>
    </div>
</div>

<?php
$this->registerJsFile('/js/entrance.view.js?v=1507457981');
$this->registerJs('
    var entrance = new EntranceView();
', View::POS_READY, 'entrance-js');
