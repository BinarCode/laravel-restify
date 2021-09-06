<template>
    <div class="toggle-tip" :class="{ open: isOpen, expands: enableExpand, collapses: enableCollapse }">
        <label v-if="title" class="title">{{ title }}</label>
        <div class="wrapper" :style="{ 'max-height': isOpen ? 'none' : height + 'px' }">
            <slot></slot>
        </div>
        <div v-if="(! isOpen && enableExpand) || (isOpen && enableCollapse)" class="expander" @click="toggle()">
            {{ isOpen ? collapseTerm : expandTerm }}
        </div>
    </div>
</template>

<style>
.toggle-tip {
    @apply my-6;

    .title {
        @apply font-medium mb-1 mt-6 block;
    }

    .wrapper {
        @apply overflow-y-auto z-0 rounded-t;
        border: 1px solid var(--custom-block-border-color);
        border-bottom: 0;

        div[class*="language-"] {
            margin-top: -0.5rem;
            margin-bottom: -0.5rem;
            border-radius: 0;
        }
    }

    .expander {
        @apply text-center font-medium cursor-pointer select-none z-10 relative text-sm rounded-b py-1;
        border: 1px solid var(--custom-block-border-color);
        color: #476582;
        font-size: 15px;

        &:hover {
            box-shadow: 0 0 16px rgba(74, 124, 246, 0.1);
        }
    }

    &.open {
        .wrapper {
            @apply rounded-b;
            max-height: none;
        }

        &.collapses {
            .wrapper {
                @apply rounded-b-none;
            }
        }
    }
}
</style>

<script>
export default {
    props: {
        title: {
            type: String,
            default: "",
            required: false
        },
        height: {
            type: Number,
            default: 300,
            required: false
        },
        expandTerm: {
            type: String,
            default: "expand"
        },
        collapseTerm: {
            type: String,
            default: "collapse"
        },
        enableExpand: {
            type: Boolean,
            default: true,
            required: false
        },
        enableCollapse: {
            type: Boolean,
            default: false,
            required: false
        }
    },
    data() {
        return {
            isOpen: false
        }
    },
    methods: {
        toggle() {
            this.isOpen = ! this.isOpen;
        }
    }
};
</script>
