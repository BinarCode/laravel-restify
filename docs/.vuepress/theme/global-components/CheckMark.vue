<template>
  <svg
    role="img"
    :aria-hidden="label ? false : true"
    :aria-label="label"
    focusable="false"
    :width="width"
    :height="height"
    viewBox="0 0 512 512"
    fill="#27AB83"
  >
    <path
      d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"
    ></path>
  </svg>
</template>

<script>
export default {
  props: {
    label: {
      type: String,
      required: false,
      default: 'Yes',
    },
    width: {
      type: Number,
      default: 18
    },
    height: {
      type: Number,
      default: 18
    }
  }
};
</script>
