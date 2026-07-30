import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

/** 任意の文字列項目。
 *  YAMLで「weight:」のようにキーだけ書くと null になります。手書き編集では
 *  よくあるので、null は「未記入」として受け取ってビルドを止めないようにします。 */
const optStr = z.preprocess((v) => (v === null || v === '' ? undefined : v), z.string().optional());

/** 読み物（FB資産279本から。v1は5本で開始）
 *  カテゴリはv1では3つに絞る → 記事30本を超えたら6つに分割 */
const blog = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/blog' }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    category: z.enum(['心とエネルギー', 'お金と仕事', '神社と参拝']),
    tags: z.array(z.string()).default([]),
    pubDate: z.date(),
    heroImage: z.string().optional(),
    draft: z.boolean().default(false),
  }),
});

/** 石（1点＝1ファイル。すべて一点もの）
 *  ⚠️「タントラ用」は表に出さない → STRATEGY 第6章 */
const ishi = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/ishi' }),
  schema: z.object({
    id: z.string(),
    name: z.string(),
    price: z.number(),
    size: optStr,
    material: z.string(),
    use: z.enum(['瞑想', '場を整える']),
    /** 重さ。石はサイズだけでは伝わらないので必ず測る */
    weight: optStr,
    soldout: z.boolean().default(false),
    stripeLink: z.string().url().optional(),

    photos: z.array(z.string()).min(1),
    /** 一覧カード用。未指定なら photos[0] を使う */
    cardPhoto: optStr,

    /** 石を回して見せる短い動画。
     *  🔴 タイガーアイのシャトヤンシー（光の帯が動く）のように、
     *     写真では絶対に伝わらない性質がある石には必ず入れる。
     *     preload="none" なので、再生ボタンを押すまで通信は発生しません。 */
    video: optStr,
    videoPoster: optStr,
    videoNote: optStr,

    /** 石の種類そのものの基本情報（鉱物としての事実）。
     *  🔴 ここに「効能」を書かないこと。書いてよいのは検証できる事実だけ。
     *     岡さんの見立ては本文の「岡の見立て」に分けて書きます。 */
    basics: z
      .object({
        jpName: optStr,      // 和名
        mineral: optStr,     // 鉱物種・グループ
        formula: optStr,     // 化学組成
        hardness: optStr,    // モース硬度
        origin: optStr,      // 主な産地
        feature: optStr,     // 見た目の特徴（光学効果など）
      })
      .optional(),

    pubDate: z.date(),
  }),
});

/** 神遊び（神社ツアー）1回＝1ファイル。
 *
 *  データは2ブロックに分かれています。
 *    ① 募集用 …… 開催前に埋める。これから募集する回だけ必須
 *    ② 記録用 …… 開催後に足す。ある回だけでよい（写真だけでもOK）
 *
 *  🔴 過去の回は ① をほとんど空にできます（覚えていない・記録がないため）。
 *     state: 終了 のときは募集用の必須チェックが外れます。
 *  ⚠️ 残席は持たせません（人数は個別連絡で調整するため）。定員だけ。
 *  🔴 参加費に「移動代」を含めない → TRAVEL_LAW_MEMO / 03b 第2章
 */
const tours = defineCollection({
  // `_` で始まるファイル（_TEMPLATE.md）は読み込まない
  loader: glob({ pattern: '**/[!_]*.md', base: './src/content/tours' }),
  schema: z
    .object({
      // ══════════ どの回にも必要（過去回も含む） ══════════
      title: z.string(),
      /** 一覧に出る短い説明（40〜60字） */
      lead: z.string(),
      date: z.date(),
      /** 通常＝半日〜1日・1〜2社／節目＝複数社をめぐる大きな回。
       *  🔴 どちらも永田町駅に集合して車で回ります（2026-07-30 実態確認） */
      kind: z.enum(['通常', '節目']),
      /** 仕事運 / 金運 / 心を整える など */
      theme: z.string(),
      area: z.string(),
      state: z.enum(['準備中', '受付中', '満席', '終了']).default('準備中'),
      heroImage: z.string().optional(),
      draft: z.boolean().default(false),

      // ══════════ ① 募集用（開催前に埋める） ══════════
      // 受付中・満席のときは下の superRefine で必須チェックが走ります
      meetPlace: z.string().optional(),
      meetTime: z.string().optional(),
      endPlace: z.string().optional(),
      endTime: z.string().optional(),
      capacity: z.number().optional(),

      /** 初回 / リピーター / ご紹介 の3段。リピート率を上げる中核の仕掛け */
      prices: z
        .array(z.object({ label: z.string(), amount: z.number(), note: z.string().optional() }))
        .optional(),
      includes: z.array(z.string()).default([]),
      excludes: z.array(z.string()).default([]),
      payment: z.string().default('銀行振込／クレジットカード'),

      /** 🔒 内部用。サイトには出しません。
       *  移動の実態を記録しておく欄（旅行業法の判断に直結するので正直に）。 */
      transport: z
        .enum(['公共交通（各自）', '同乗（岡さんの車・実費を割り勘）', '徒歩圏のみ'])
        .optional(),
      /** 👀 サイトに出る移動の書き方。「車で相乗りして回ります」など */
      transportNote: z.string().optional(),
      /** 👀 移動実費の目安。🔴 金額は伏せない → 03b 第2d章 */
      transportFee: z.string().optional(),

      applyUrl: z.string().optional(),

      /** 行程。しおり（Travel/Shared_Docs/shrine_tour_2026）の形を部品化したもの */
      itinerary: z
        .array(
          z.object({
            time: z.string(),
            name: z.string(),
            /** 【金運・産業創始の神】など、その社の見立て */
            kami: z.string().optional(),
            desc: z.string().optional(),
            address: z.string().optional(),
            /** 次の地点までの移動メモ。例「2時間30分（アクアライン経由）」 */
            move: z.string().optional(),
            /** 🔴 その道中で岡さんが話すこと。車中は講義の時間 → 03b 第2b章 */
            moveTalk: z.string().optional(),
          })
        )
        .default([]),

      faq: z.array(z.object({ q: z.string(), a: z.string() })).default([]),

      // ══════════ ② 記録用（開催後・ある回だけ） ══════════
      /** 🔴 全部そろえる必要はありません。**写真だけでも成立します**。
       *     文章がなくても、写真が並ぶだけで「どんな一日か」は伝わるので。 */
      report: z
        .object({
          /** その日どうだったか。3〜5行。なくてよい */
          summary: z.string().optional(),
          /** 予定から変えたところと理由。隠さない → 03b 第2c章 */
          changed: z.string().optional(),
          photos: z
            .array(z.object({ src: z.string(), caption: z.string().optional() }))
            .default([]),
          /** 🔴 掲載許諾を得た声だけ。顔写真は載せません */
          voices: z.array(z.object({ text: z.string(), who: z.string() })).default([]),
        })
        .optional(),
    })
    // 募集する回だけ、①の必須チェックを走らせる。
    // 過去回（終了）は空でも通ります＝古い回を軽く登録できる
    .superRefine((d, ctx) => {
      if (d.state !== '受付中' && d.state !== '満席') return;
      const required: [unknown, string][] = [
        [d.meetPlace, 'meetPlace（集合場所）'],
        [d.meetTime, 'meetTime（集合時刻）'],
        [d.endPlace, 'endPlace（解散場所）'],
        [d.endTime, 'endTime（解散時刻）'],
        [d.capacity, 'capacity（定員）'],
        [d.prices?.length ? d.prices : undefined, 'prices（参加費）'],
        [d.includes.length ? d.includes : undefined, 'includes（含まれるもの）'],
        [d.excludes.length ? d.excludes : undefined, 'excludes（含まれないもの）'],
        [d.transport, 'transport（移動の実態・内部用）'],
      ];
      for (const [v, name] of required) {
        if (v === undefined || v === null || v === '') {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `募集する回（state: ${d.state}）には ${name} が必要です`,
          });
        }
      }
    }),
});

export const collections = { blog, ishi, tours };
