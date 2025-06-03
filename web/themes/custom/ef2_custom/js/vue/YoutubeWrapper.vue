<script setup>
import { reactive, computed, defineProps, ref } from 'vue'
import YouTube from 'vue3-youtube'

const props = defineProps(['iframeSrc', 'thumbnailSrc'])

const youtubePlayer = ref(null);

const state = reactive({ showThumbnail: true })

const hasYoutubeVideoSrc = computed(() => {
    return ! _.isEmpty(props.iframeSrc)
})

const hasThumbnailSrc = computed(() => {
    return ! _.isEmpty(props.thumbnailSrc)
})

function handlePlayClick() {
    state.showThumbnail = false
    youtubePlayer.value.playVideo()
}
</script>

<template>
    <div class="vue-youtube-wrapper">
        <template v-if="hasYoutubeVideoSrc">
            <div class="container">
                <div class="inner">
                    <div class="row">
                        <div class="col-lg-10 col-xl-8 mx-auto">
                            <div class="video-wrapper">
                                <!-- <template v-if="state.showThumbnail && hasThumbnailSrc">
                                    <img :src="thumbnailSrc" class="thumbnail-image" @click="handlePlayClick">
                                    <button class="play-button" @click="handlePlayClick">Play</button>
                                </template>-->

                                <YouTube :src="iframeSrc" ref="youtubePlayer" class="iframe-wrapper" width="100%" height="100%"></YouTube>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.thumbnail-image {
    width: 100%;
    height: auto;
    cursor: pointer;
}
.play-button {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    border-radius: 50%;
    padding: 10px;
    cursor: pointer;
    font-size: 16px;
}
.video-wrapper {
    position: relative;
}
.iframe-wrapper {
    width: 100%;
    height: 100%;
}
</style>