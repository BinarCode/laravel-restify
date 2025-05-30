import theme from '@nuxt/content-theme-docs';

export default theme({
  modules: [
    '@nuxtjs/gtm',
  ],
  gtm: {
    id: 'G-T3S2WB57KE',
  },
  docs: {
    primaryColor: '#787af6'
  },
  head: {
    script: [
      {
        src: '//code.tidio.co/pgx3d8jlrufene0cnyv274lgege4u5c1.js',
        async: true,
      }
    ]
  }
});
