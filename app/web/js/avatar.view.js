var AvatarView = function (data) {
    data = data || {};

    var defaults = {
        'maxUploadSize': 15, // in mb

        // form selectors
        'avatarImg':           '.avatar-img',
        'cropHotspot':         '#crop_hotspot',
        'avatarPopup':         '#avatar_popup',
        'uploadContainer':     '#upload_container',
        'previewContainer':    '#preview_container',
        'previewImg':          '#preview_img',
        'previewRemoveHandle': '#preview_remove',
        'saveAvatarHandle':    '#persist_avatar',
        'deleteAvatarHandle':  '.delete-avatar',

        // ajax urls
        'ajaxTempAvatarUploadUrl': '',
        'ajaxSaveAvatarUrl':       '',
        'ajaxDeleteAvatarUrl':     ''
    };

    this.settings = $.extend({}, defaults, data);

    this.hotspotsInst = null;

    // commonly used selectors
    this.$avatarPopup      = $(this.settings.avatarPopup);
    this.$uploadContainer  = $(this.settings.uploadContainer);
    this.$previewContainer = $(this.settings.previewContainer);
    this.$previewImg       = $(this.settings.previewImg);
    this.$cropHotspot      = $(this.settings.cropHotspot);

    // xhr identifiers
    this.saveAvatarXHR   = null;
    this.deleteAvatarXHR = null;

    this.init();
};

/**
 * Init method
 */
AvatarView.prototype.init = function () {
    var self      = this;
    var $document = $(document);

    self.tempAvatarUpload();

    $document.off('click.avatarview', self.settings.saveAvatarHandle);
    $document.on('click.avatarview', self.settings.saveAvatarHandle, function (e) {
        e.preventDefault();

        self.saveAvatar();
    });

    $document.off('click.avatarview', self.settings.deleteAvatarHandle);
    $document.on('click.avatarview', self.settings.deleteAvatarHandle, function (e) {
        e.preventDefault();

        self.deleteAvatar();
    });

    self.initAvatarCrop();

    self.$previewImg.off('load.avatarview');
    self.$previewImg.on('load.avatarview', function () {
        self.repositionCrop();
    });

    self.$avatarPopup.off('popupOpen.avatarview');
    self.$avatarPopup.on('popupOpen.avatarview', function (e) {
        if (self.$previewImg.data('preview-url')) {
            // reset preview image
            self.$previewImg.attr('src', PR.nocacheUrl(self.$previewImg.data('preview-url'))).show();
            self.$uploadContainer.hide();
            self.$previewContainer.show();
        }

        self.repositionCrop();
    });
};

/**
 * Init avatar crop container.
 */
AvatarView.prototype.initAvatarCrop = function () {
    this.hotspotsInst = new Hotspots({
        drawContainer: '.preview-image-wrapper',
        drawLayer:     '.preview-image',
        maxHotspots:   1,
        squareScale:   true,
        minWidth:      100,
        minHeight:     100
    });

    this.hotspotsInst.init();
}

/**
 * Sets default dimensions to the crop hotspot elem.
 */
AvatarView.prototype.repositionCrop = function () {
    var self = this;

    if (!self.$cropHotspot.length || !self.$previewImg.length) {
        return;
    }

    var ratio     = self.$previewImg[0].naturalWidth / self.$previewImg.width();
    var imgWidth  = self.$previewImg.width();
    var imgHeight = self.$previewImg[0].naturalHeight / ratio;

    var size, left, top;
    if (imgWidth < imgHeight) {
        size = imgWidth;
        left = 0;
        top = (imgHeight / 2) - (size / 2);
    } else {
        size = imgHeight;
        left = (imgWidth / 2) - (size / 2);
        top = 0;
    }

    self.$cropHotspot.css({
        'left':   left,
        'top':    top,
        'width':  size,
        'height': size
    });
};

/**
 * Handles temp avatar upload.
 */
AvatarView.prototype.tempAvatarUpload = function () {
    var self = this;

    Dropzone.autoDiscover = false;
    var myDropzone = new Dropzone(self.settings.uploadContainer, {
        url:                   self.settings.ajaxTempAvatarUploadUrl,
        paramName:             'AvatarForm[avatar]',
        uploadMultiple:        false,
        thumbnailWidth:        null,
        thumbnailHeight:       null,
        addRemoveLinkss:       false,
        createImageThumbnails: false,
        previewTemplate:       '<div style="display: none"></div>',
        acceptedFiles:         'image/*',
        maxFiles:              1,
        maxFilesize:           self.settings.maxUploadSize
    });

    myDropzone.on('sending', function (file, xhr, formData) {
        formData.append(yii.getCsrfParam(), yii.getCsrfToken());
        self.$uploadContainer.show().addClass('loading');
    });

    myDropzone.on('complete', function (file, xhr, formData) {
        self.$uploadContainer.removeClass('loading');
        myDropzone.removeAllFiles(true);
    });

    myDropzone.on('success', function (file, response) {
        if (response.success) {
            self.$uploadContainer.hide();
            self.$previewContainer.show();

            if (response.tempAvatarUrl) {
                self.$previewImg.attr('src', PR.nocacheUrl(response.tempAvatarUrl)).show();
            }
        }

        PR.addNotification(response.message, response.success ? 'success' : 'danger');
    });

    $(document).on('deleteAvatar', function (e) {
        myDropzone.removeAllFiles(true);
        self.$previewImg.attr('src', '').data('preview-url', '');
        self.$uploadContainer.show();
        self.$previewContainer.hide();
    });

    $(document).on('click.profileView', self.settings.previewRemoveHandle, function (e) {
        e.preventDefault();

        myDropzone.removeAllFiles(true);
        self.$previewImg.attr('src', '');
        self.$uploadContainer.show();
        self.$previewContainer.hide();
    });
};

/**
 * Saves avatar and generate thumb according to the crop dimensions.
 */
AvatarView.prototype.saveAvatar = function () {
    var self = this;

    var ratio = self.$previewImg.get(0).naturalWidth / self.$previewImg.width();
    var crop = {
        x: (self.$cropHotspot.position().left * ratio) || 0,
        y: (self.$cropHotspot.position().top * ratio)  || 0,
        w: self.$cropHotspot.width() * ratio,
        h: self.$cropHotspot.height() * ratio
    };

    var isTemp = self.$previewImg.attr('src').indexOf('avatar_temp.jpg') >= 0;

    PR.abortXhr(self.saveAvatarXHR);
    self.saveAvatarXHR = $.ajax({
        url: self.settings.ajaxSaveAvatarUrl,
        type: 'POST',
        data: {
            'crop':   crop,
            'isTemp': isTemp ? 1 : 0,
        },
    }).done(function (response) {
        if (response.success) {
            PR.closePopup(self.$avatarPopup);

            if (response.avatarUrl && response.avatarThumbUrl) {
                setTimeout(function() {
                    self.$previewImg.data('preview-url', response.avatarUrl);
                    $(self.settings.deleteAvatarHandle).show();
                    $(self.settings.avatarImg).show().attr('src', PR.nocacheUrl(response.avatarThumbUrl));
                }, 100); // animations delay
            }
        }
    });
};

/**
 * Delete avatar and its thumb via ajax.
 */
AvatarView.prototype.deleteAvatar = function () {
    var self = this;

    PR.abortXhr(self.deleteAvatarXHR);
    self.deleteAvatarXHR = $.ajax({
        url: self.settings.ajaxDeleteAvatarUrl,
        type: 'POST',
    }).done(function (response) {
        if (response.success) {
            PR.closePopup(self.$avatarPopup);

            $(self.settings.deleteAvatarHandle).hide();
            $(self.settings.avatarImg).attr('src', '');

            $(document).trigger('deleteAvatar');
        }
    });
};
