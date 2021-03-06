<template>
    <div class="SharpUpload" :class="[{'SharpUpload--empty':!file, 'SharpUpload--disabled':readOnly}, modifiersClasses]">
        <div :class="{ 'card card-body': file }">
            <form v-show="!file" class="dropzone">
                <Button class="dz-message" text block :disabled="readOnly" type="button" ref="button">
                    {{ l('form.upload.browse_button') }}
                </Button>
            </form>
            <template v-if="file">
                <div class="SharpUpload__container" :class="{ row:showThumbnail }">
                    <div v-if="showThumbnail" class="SharpUpload__thumbnail" :class="[modifiers.compacted?'col-4 col-sm-3 col-xl-2':'col-4 col-md-4']">
                        <img :src="imageSrc" @load="handleImageLoaded">
                    </div>
                    <div class="SharpUpload__infos" :class="{[modifiers.compacted?'col-8 col-sm-9 col-xl-10':'col-8 col-md-8']:showThumbnail}">
                        <div class="mb-3">
                            <label class="SharpUpload__filename text-truncate d-block">{{ fileName }}</label>
                            <div class="SharpUpload__info mt-2">
                                <div class="row g-2">
                                    <template v-if="size">
                                        <div class="col-auto">{{ size }}</div>
                                    </template>
                                    <template v-if="canDownload">
                                        <div class="col-auto">
                                            <a class="SharpUpload__download-link" :href="downloadUrl" :download="fileName">
                                                <i class="fas fa-download"></i>
                                                {{ l('form.upload.download_link') }}
                                            </a>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <transition name="SharpUpload__progress">
                                <div class="SharpUpload__progress mt-2" v-show="inProgress">
                                    <div class="SharpUpload__progress-bar" role="progressbar" :style="{width:`${progress}%`}"
                                         :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </transition>
                        </div>
                        <div v-show="!readOnly">
                            <Button v-show="!!originalImageSrc && !inProgress" outline small :disabled="!isCroppable" @click="onEditButtonClick">
                                {{ l('form.upload.edit_button') }}
                            </Button>
                            <Button class="SharpUpload__remove-button" variant="danger" outline small :disabled="readOnly" @click="remove()">
                                {{ l('form.upload.remove_button') }}
                            </Button>
                        </div>
                    </div>
                </div>
            </template>
            <div ref="clip-preview-template" class="clip-preview-template" style="display: none;">
                <div></div>
            </div>
        </div>
        <template v-if="!!originalImageSrc && isCroppable">
            <Modal :visible.sync="showEditModal"
                @ok="onEditModalOk"
                @hidden="onEditModalHidden"
                no-close-on-backdrop
                :title="l('modals.cropper.title')"
                ref="modal"
            >
                <vue-cropper
                    class="SharpUpload__modal-vue-cropper"
                    :view-mode="2"
                    drag-mode="crop"
                    :aspect-ratio="ratioX/ratioY"
                    :auto-crop-area="1"
                    :zoomable="false"
                    :guides="false"
                    :background="true"
                    :rotatable="true"
                    :src="originalImageSrc"
                    :data="cropData"
                    alt="Source image"
                    ref="modalCropper"
                />
                <div class="mt-3">
                    <Button @click="rotate(-90)"><i class="fas fa-undo"></i></Button>
                    <Button @click="rotate(90)"><i class="fas fa-redo"></i></Button>
                </div>
            </Modal>
            <vue-cropper
                class="d-none"
                :aspect-ratio="ratioX/ratioY"
                :auto-crop-area="1"
                :src="originalImageSrc"
                :ready="onCropperReady"
                ref="cropper"
            />
        </template>
        <a style="display: none" ref="dlLink"></a>
    </div>
</template>

<script>
    import VueClip from 'vue-clip/src/components/Clip';
    import VueCropper from 'vue-cropperjs';

    import { Modal, Button } from 'sharp-ui';
    import { Localization } from 'sharp/mixins';
    import { filesizeLabel } from 'sharp';

    import { VueClipModifiers } from './modifiers';
    import rotateResize from './rotate';
    import { downloadFileUrl } from "../../../api";

    export default {
        name: 'SharpVueClip',

        extends: VueClip,

        components: {
            Modal,
            VueCropper,
            Button,
        },

        inject : [ 'axiosInstance' ,'$form', '$field' ],

        mixins: [ Localization, VueClipModifiers ],

        props: {
            downloadId: String,
            pendingKey: String,
            ratioX: Number,
            ratioY: Number,
            value: Object,
            croppableFileTypes:Array,

            readOnly: Boolean,
            root: Boolean,
        },

        data() {
            return {
                showEditModal: false,
                croppedImg: null,
                allowCrop: false,
                cropData: null,

                isNew: !this.value,
                canDownload: !!this.value,
            }
        },
        watch: {
            value(value) {
                if(!value) {
                    this.files = [];
                }
            },
            'file.status'(status) {
                (status in this.statusFunction) && this[this.statusFunction[status]]();
            },
        },
        computed: {
            file() {
                return this.files[0];
            },
            originalImageSrc() {
                return this.file && (this.file.thumbnail || this.file.dataUrl);
            },
            imageSrc() {
                return this.croppedImg || this.originalImageSrc;
            },
            size() {
                return this.file.size != null
                    ? filesizeLabel(this.file.size)
                    : null;
            },
            operationFinished() {
                return {
                    crop: this.hasInitialCrop ? !!this.croppedImg : null
                }
            },
            operations() {
                return Object.keys(this.operationFinished);
            },
            activeOperationsCount() {
                return this.operations.filter(op => this.operationFinished[op] !== null).length;
            },
            operationFinishedCount() {
                return this.operations.filter(op => this.operationFinished[op]).length;
            },
            progress() {
                let curProgress = this.file ? this.file.progress : 100;

                let delta = this.activeOperationsCount - this.operationFinishedCount;
                let factor = (1-delta*.05);

                return Math.floor(curProgress) * factor;
            },
            inProgress() {
                return (this.file && this.file.status !== 'exist') && this.progress < 100;
            },
            statusFunction() {
                return {
                    error:'onStatusError', success:'onStatusSuccess', added:'onStatusAdded'
                }
            },
            fileName() {
                let splitted = this.file.name.split('/');
                return splitted.length ? splitted[splitted.length-1] : '';
            },
            fileExtension() {
                let extension = this.fileName.split('.').pop();
                return extension ? `.${extension}` : null;
            },
            downloadUrl() {
                return downloadFileUrl({
                    entityKey: this.$form.entityKey,
                    instanceId: this.$form.instanceId,
                    fieldKey: this.downloadId,
                    fileName: this.fileName,
                });
            },
            showThumbnail() {
                return this.imageSrc;
            },
            hasInitialCrop() {
                return !!(this.ratioX && this.ratioY) && this.isCroppable;
            },
            isCroppable() {
                if(this.file?.type && !this.file.type.match(/^image\//)) {
                    return false;
                }
                return !this.croppableFileTypes || this.croppableFileTypes.includes(this.fileExtension);
            }
        },
        methods: {
            setPending(value) {
                this.$form?.setUploading(this.pendingKey, value);
            },
            // status callbacks
            onStatusAdded() {
                this.$emit('reset');

                this.setPending(true);
            },
            onStatusError() {
                let msg = this.file.errorMessage;
                this.remove();
                this.$emit('error', msg);
            },
            onStatusSuccess() {
                let data = {};
                try {
                    data = JSON.parse(this.file.xhrResponse.responseText);
                }
                catch(e) { console.log(e); }

                data.uploaded = true;
                this.$emit('success', data);
                this.$emit('input',data);

                this.setPending(false);

                this.allowCrop = true;
                this.$nextTick(() => {
                    this.isCropperReady() && this.onCropperReady();
                });
            },

            // actions
            remove() {
                this.canDownload = false;
                this.removeFile(this.file);
                this.files.splice(0, 1);

                this.setPending(false);

                this.resetEdit();

                this.$emit('input', null);
                this.$emit('reset');
                this.$emit('removed');
            },

            resetEdit() {
                this.croppedImg = null;
                this.cropData = null;
            },

            onEditButtonClick() {
                this.$emit('active');
                this.showEditModal = true;
                this.allowCrop = true;
            },

            handleImageLoaded() {
                if(this.isNew) {
                    this.$emit('image-updated');
                }
            },

            onEditModalHidden() {
                this.$emit('inactive');
            },

            onEditModalOk() {
                this.updateCroppedImage(this.$refs.modalCropper);
                this.updateCropData(this.$refs.modalCropper);
            },

            isCropperReady() {
                return this.$refs.cropper && this.$refs.cropper.cropper.ready;
            },

            onCropperReady() {
                if(this.hasInitialCrop) {
                    this.updateCroppedImage(this.$refs.cropper);
                    this.updateCropData(this.$refs.cropper);
                }
            },

            updateCropData(cropper) {
                let cropData = cropper.getData(true);
                let imgData = cropper.getImageData();

                let rw=imgData.naturalWidth, rh=imgData.naturalHeight;

                if(Math.abs(cropData.rotate)%180) {
                    rw = imgData.naturalHeight;
                    rh = imgData.naturalWidth;
                }

                let relativeData = {
                    width: cropData.width / rw,
                    height: cropData.height / rh,
                    x: cropData.x / rw,
                    y: cropData.y / rh,
                    rotate: cropData.rotate * -1 // counterclockwise
                };

                this.cropData = { ...cropData };

                if(this.allowCrop) {
                    let data = {
                        ...this.value,
                        cropData: relativeData,
                    };
                    this.$emit('input', data);
                    this.$emit('updated', data);
                }
            },

            updateCroppedImage(cropper) {
                if(this.allowCrop) {
                    this.isNew = true;
                    this.croppedImg = cropper.getCroppedCanvas().toDataURL();
                }
            },

            rotate(degree) {
                rotateResize(this.$refs.modalCropper.cropper, degree);
            },
        },
        created() {
            this.options.thumbnailWidth = null;
            this.options.thumbnailHeight = null;
            this.options.maxFiles = 1;

            if (!this.value)
                return;

            this.addedFile({ ...this.value, upload: {} });
            this.file.thumbnail = this.value.thumbnail;
            this.file.status = 'exist';
        },
        mounted() {
            const button = this.$refs.button.$el;
            this.uploader._uploader.disable();
            this.uploader._uploader.listeners.forEach(listener => listener.element = button);
            this.uploader._uploader.clickableElements = [button];
            this.uploader._uploader.enable();
        },
        beforeDestroy() {
            this.setPending(false);
            this.uploader._uploader.destroy();
        },
    }
</script>
