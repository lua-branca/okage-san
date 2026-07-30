// @ts-check
import { defineConfig } from 'astro/config';
import remarkBreaks from 'remark-breaks';

// https://astro.build/config
export default defineConfig({
  site: 'https://lua-branca.github.io',
  base: '/okage-san',
  markdown: {
    // 🔴 岡さんの文章は1行13字前後の改行が文体そのもの（TONE_GUIDE実測）。
    //    remark-breaks を入れないと単一改行が消えて、別人の文章になる。
    remarkPlugins: [remarkBreaks],
    gfm: true,
    smartypants: false,
  },
  build: {
    // preserve: ファイル構造どおりに出力
    // ルート直下 → xxx.html／サブフォルダのindex → folder/index.html
    // public/ 配下のPHP（form/ api/ mypage/）はそのまま dist/ にコピーされる
    format: 'preserve',
  },
});
