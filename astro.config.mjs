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
    // 🔴 directory：全ページを about/index.html の形で出力します。
    //    'preserve' だと about.html になり、サイト内リンク（/about/）が
    //    GitHub Pages で 404 になりました（2026-07-30 修正）。
    //    Xserver（Apache）でも directory のほうが素直に動きます。
    //    ※ public/ 配下のPHPは format に関係なくそのまま dist/ にコピーされます
    format: 'directory',
  },
});