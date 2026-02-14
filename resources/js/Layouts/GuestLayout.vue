<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div
        class="min-vh-100 d-flex align-items-center position-relative overflow-hidden px-3 py-4 px-sm-4 py-sm-5"
        style="background: #0b1220;"
    >
        <img
            src="/images/EldoriaMap.png"
            alt="Eldoria map background"
            class="position-absolute top-0 start-0 w-100 h-100"
            style="object-fit: cover; opacity: 0.75; z-index: 0;"
        />

        <div
            class="position-absolute top-0 start-0 w-100 h-100"
            style="background: linear-gradient(rgba(7, 12, 22, 0.3), rgba(7, 12, 22, 0.42)); z-index: 1;"
        ></div>

        <div class="container position-relative" style="z-index: 2; max-width: 1024px;">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-7 col-xl-6">
                    <div
                        class="card border-0 position-relative shadow"
                        style="background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(3px); border-radius: 1rem;"
                    >
                        <div class="card-body p-3 p-sm-4 p-lg-5">
                            <div v-if="page.props.flash?.error" class="alert alert-danger border-0 mb-3" role="alert">
                                {{ page.props.flash.error }}
                            </div>

                            <div v-if="page.props.flash?.warning" class="alert alert-warning border-0 mb-3" role="alert">
                                {{ page.props.flash.warning }}
                            </div>

                            <div v-if="page.props.flash?.info" class="alert alert-info border-0 mb-3" role="status" aria-live="polite">
                                {{ page.props.flash.info }}
                            </div>

                            <div v-if="page.props.flash?.status" class="alert alert-success border-0 mb-3" role="status" aria-live="polite">
                                {{ page.props.flash.status }}
                            </div>

                            <slot />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
