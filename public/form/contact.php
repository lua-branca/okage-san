<?php
/**
 * お問い合わせフォーム（2026-08-07 新設）
 *
 * ツアー申込フォーム（index/confirm/complete の3ファイル）と違い、
 * 1ファイルでステップを切り替えます。
 * 理由：入力画面が Astro の静的HTML（/contact/）なので「修正する」で
 *       値を持ったまま戻せません。編集画面をこのPHP側に持つ必要があります。
 *
 * 流れ：
 *   /contact/（Astro）→ POST → [確認] → POST → [送信] → /contact/thanks/
 *                                 ↑         ↓
 *                                 └─[編集]─┘
 *
 * GASもスプレッドシートも使いません。メール2通を送るだけです（2026-08-07 決定）。
 */

session_start();

// ─────────────────────────────────────────────
// 【文面の設定】文言を直すときは、このブロックだけ触れば済みます。
//   将来スプレッドシートの `98_メール文面` に移す場合も、ここを差し替えます。
// ─────────────────────────────────────────────

// 送信元（自ドメイン固定。送信者のアドレスを From にすると
// SPF/DMARC 認証に失敗して迷惑メール行きになります）
const MAIL_FROM_ADDRESS  = 'contact@okage-san.com';
const MAIL_FROM_NAME     = 'マインドフリー神道学アカデミア';
const MAIL_NOTIFY_NAME   = 'お問い合わせフォーム'; // 社内通知の差出人表示名
const MAIL_NOTIFY_TO     = 'contact@okage-san.com';

// 自動返信（お客様へ）の件名。カテゴリを入れず固定にしています。
// お客様が後から「お問い合わせ」で検索したとき、過去分が全部並ぶためです。
const MAIL_REPLY_SUBJECT = '【マインドフリー神道学アカデミア】お問い合わせありがとうございます';

/** 種類の値を、画面に出している文言に直す（例：整える石について → パワーストーンについて） */
function mfs_category_label(string $value): string {
    return CATEGORIES[$value] ?? $value;
}

/** 自動返信メールの本文を組み立てる */
function mfs_build_reply_body(array $d): string {
    $name_line = $d['name'];
    if ($d['kana'] !== '') {
        $name_line .= '（' . $d['kana'] . '）';
    }

    $b  = "{$d['name']} 様\n\n";
    $b .= "このたびはお問い合わせいただき、誠にありがとうございます。\n";
    $b .= "下記の内容で承りました。\n\n";
    $b .= "----------------------------------------\n";
    $b .= "■ お名前: {$name_line}\n";
    $b .= "■ メールアドレス: {$d['email']}\n";
    $b .= "■ お問い合わせの種類: " . mfs_category_label($d['category']) . "\n";
    $b .= "■ お問い合わせ内容:\n{$d['message']}\n";
    $b .= "----------------------------------------\n\n";
    $b .= "内容を確認のうえ、通常2〜3営業日以内に\n";
    $b .= "担当者よりご返信いたします。\n\n";
    $b .= "数日たってもお返事が届かない場合、\n";
    $b .= "迷惑メールフォルダに振り分けられている可能性がございます。\n";
    $b .= "お手数ですが、ご確認のうえ再度ご連絡ください。\n\n";
    $b .= "※ このメールは自動送信です。\n";
    $b .= "　 ご返信いただいた場合も、担当者が確認いたします。\n\n";
    $b .= "感謝してます。\n\n";
    $b .= "岡 慶行\n";
    $b .= "マインドフリー神道学アカデミア\n";
    $b .= "https://okage-san.com\n";
    $b .= MAIL_FROM_ADDRESS . "\n";

    return $b;
}

/** 社内通知メールの本文を組み立てる */
function mfs_build_notify_body(array $d): string {
    $b  = "Webサイトのお問い合わせフォームから送信がありました。\n\n";
    $b .= "----------------------------------------\n";
    $b .= "■ お名前: {$d['name']} 様\n";
    $b .= "■ ふりがな: " . ($d['kana'] !== '' ? $d['kana'] : '（未記入）') . "\n";
    $b .= "■ メールアドレス: {$d['email']}\n";
    $b .= "■ お問い合わせの種類: " . mfs_category_label($d['category']) . "\n";
    $b .= "■ お問い合わせ内容:\n{$d['message']}\n";
    $b .= "----------------------------------------\n\n";
    $b .= "■ 受信日時: " . date('Y-m-d H:i:s') . "\n";
    $b .= "■ 送信元IP: " . ($_SERVER['REMOTE_ADDR'] ?? '不明') . "\n";
    $b .= "■ ブラウザ: " . mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '不明', 0, 200) . "\n\n";
    $b .= "このメールに返信すると、お客様に直接届きます。\n";

    return $b;
}

// ─────────────────────────────────────────────
// 【共通の設定】
// ─────────────────────────────────────────────

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tokyo');

// お問い合わせの種類（contact.astro の <option> と一致させること）。
// 「送信される値 => 画面に出す文言」。件名に載る値なので、ホワイトリスト照合します。
const CATEGORIES = [
    '神社参拝について'         => '神社参拝について',
    '整える石について'         => 'パワーストーンについて',
    '個別セッション・前世療法' => '個別セッション・前世療法について',
    '取材・講演'               => '取材・講演・メディア掲載について',
    'その他のお問い合わせ'     => 'その他のお問い合わせ',
];

// 入力の上限（超えたら弾く）
const LIMITS = [
    'name'     => 100,
    'kana'     => 100,
    'email'    => 254,
    'message'  => 5000,
];

/**
 * サイトのベースパスを自分のURLから割り出す。
 *   /okage-san/form/contact.php → /okage-san   （GitHub Pages・ローカル確認）
 *   /form/contact.php           → （空文字）    （本番 okage-san.com）
 * astro.config.mjs の base を変えても、ここは直さなくて済みます。
 */
function mfs_base_path(): string {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/form/contact.php'));
    $base = preg_replace('#/form/?$#', '', $dir);
    return ($base === '/' || $base === null) ? '' : $base;
}

/** 送信失敗など、あとで追いたいことだけ記録する（公開領域の外へ出す） */
function mfs_log(string $message): void {
    $dir = __DIR__ . '/../../mfs_logs';
    if (!is_dir($dir)) {
        return; // ローカル確認時などディレクトリが無ければ何もしない
    }
    @file_put_contents(
        $dir . '/contact.log',
        date('Y-m-d H:i:s') . ' ' . $message . "\n",
        FILE_APPEND | LOCK_EX
    );
}

/** ヘッダインジェクション対策：改行を含む値は受け付けない */
function mfs_has_newline(string $v): bool {
    return preg_match('/[\r\n]/', $v) === 1;
}

/** 日本語を含むヘッダ用の文字列をエンコードする */
function mfs_encode_header(string $text): string {
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

/** htmlspecialchars の短縮形 */
function h(?string $v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

$BASE = mfs_base_path();

// ─────────────────────────────────────────────
// 【入口】GETで直接開かれたら入力ページへ戻す
// ─────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $BASE . '/contact/');
    exit;
}

// スパムボット判定（ハニーポットに入力があれば即拒否）
if (!empty($_POST['hp_website'])) {
    exit('Spam detected.');
}

$step = $_POST['step'] ?? 'confirm';

// ─────────────────────────────────────────────
// 【送信】確認画面から「この内容で送信する」が押された
// ─────────────────────────────────────────────

if ($step === 'send') {
    $data = $_SESSION['contact_data'] ?? null;

    // CSRFトークン照合。確認画面を経ていないPOSTは通しません。
    $token = $_POST['csrf_token'] ?? '';
    if (!$data || empty($_SESSION['contact_csrf']) || !hash_equals($_SESSION['contact_csrf'], $token)) {
        $fatal = 'セッションの有効期限が切れました。お手数ですが、最初から入力しなおしてください。';
    } elseif ((time() - ($_SESSION['contact_confirm_time'] ?? 0)) < 2) {
        // 確認画面が出た2秒以内の送信は自動投稿とみなす
        $fatal = '送信を受け付けられませんでした。少し時間をおいて、もう一度お試しください。';
    }

    if (!isset($fatal)) {
        $reply_body  = mfs_build_reply_body($data);
        $notify_body = mfs_build_notify_body($data);

        $common_headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        // ① 自動返信（お客様へ）
        $reply_headers = array_merge([
            'From: ' . mfs_encode_header(MAIL_FROM_NAME) . ' <' . MAIL_FROM_ADDRESS . '>',
            'Reply-To: ' . MAIL_FROM_ADDRESS,
        ], $common_headers);

        $sent_reply = @mail(
            $data['email'],
            mfs_encode_header(MAIL_REPLY_SUBJECT),
            $reply_body,
            implode("\r\n", $reply_headers)
        );

        // ② 社内通知（contact@ へ）。Reply-To にお客様のアドレスを入れるので、
        //    受信箱で返信ボタンを押せばそのままお客様に届きます。
        // 件名だけは短い側の値を使います（受信箱で折り返さないため）
        $notify_subject = '[お問い合わせ] ' . $data['category'] . '／' . $data['name'] . ' 様';
        $notify_headers = array_merge([
            'From: ' . mfs_encode_header(MAIL_NOTIFY_NAME) . ' <' . MAIL_FROM_ADDRESS . '>',
            'Reply-To: ' . mfs_encode_header($data['name']) . ' <' . $data['email'] . '>',
        ], $common_headers);

        $sent_notify = @mail(
            MAIL_NOTIFY_TO,
            mfs_encode_header($notify_subject),
            $notify_body,
            implode("\r\n", $notify_headers)
        );

        // 社内通知が飛ばないと問い合わせ自体が消えるので、そこだけ失敗扱いにします。
        if (!$sent_notify) {
            mfs_log('通知メール送信失敗: ' . $data['email'] . ' / ' . $data['category']);
            $fatal = 'メールの送信に失敗しました。お手数ですが、contact@okage-san.com へ直接お送りください。';
        } else {
            if (!$sent_reply) {
                mfs_log('自動返信のみ失敗: ' . $data['email']);
            }
            // 二重送信を防ぐため、セッションを片付けてから完了ページへ
            unset($_SESSION['contact_data'], $_SESSION['contact_csrf'], $_SESSION['contact_confirm_time']);
            header('Location: ' . $BASE . '/contact/thanks/');
            exit;
        }
    }
}

// ─────────────────────────────────────────────
// 【編集】確認画面から「修正する」／入力エラーで戻ってきた
// ─────────────────────────────────────────────

$errors = [];

if ($step === 'edit') {
    $data = $_SESSION['contact_data'] ?? [
        'name' => '', 'kana' => '', 'email' => '', 'category' => '', 'message' => '',
    ];
} elseif ($step === 'confirm') {
    // ─── 入力ページ（Astro）から届いた ───
    $data = [
        'name'     => trim($_POST['name'] ?? ''),
        'kana'     => trim($_POST['kana'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'message'  => trim($_POST['message'] ?? ''),
    ];
    $_SESSION['contact_data'] = $data;

    if ($data['name'] === '') {
        $errors['name'] = 'お名前を入力してください。';
    } elseif (mb_strlen($data['name']) > LIMITS['name'] || mfs_has_newline($data['name'])) {
        $errors['name'] = 'お名前の形式が正しくありません。';
    }

    if ($data['kana'] !== '' && (mb_strlen($data['kana']) > LIMITS['kana'] || mfs_has_newline($data['kana']))) {
        $errors['kana'] = 'ふりがなの形式が正しくありません。';
    }

    if ($data['email'] === '') {
        $errors['email'] = 'メールアドレスを入力してください。';
    } elseif (mfs_has_newline($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = '正しいメールアドレスの形式で入力してください。';
    } elseif (mb_strlen($data['email']) > LIMITS['email']) {
        $errors['email'] = 'メールアドレスが長すぎます。';
    }

    if ($data['category'] === '') {
        $errors['category'] = 'お問い合わせの種類を選択してください。';
    } elseif (!array_key_exists($data['category'], CATEGORIES)) {
        $errors['category'] = 'お問い合わせの種類を選択しなおしてください。';
    }

    if ($data['message'] === '') {
        $errors['message'] = 'お問い合わせ内容を入力してください。';
    } elseif (mb_strlen($data['message']) > LIMITS['message']) {
        $errors['message'] = 'お問い合わせ内容は' . LIMITS['message'] . '文字以内で入力してください。';
    }

    if (empty($_POST['privacy'])) {
        $errors['privacy'] = 'プライバシーポリシーへの同意が必要です。';
    }
}

// 表示するのは、エラーがある／編集指定／送信失敗のいずれか。それ以外は確認画面。
$show_edit = !empty($errors) || $step === 'edit' || isset($fatal);

if (!$show_edit) {
    // 確認画面を出すタイミングでCSRFトークンを発行する
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
    $_SESSION['contact_confirm_time'] = time();
}

$page_title = $show_edit ? 'お問い合わせ内容の修正' : 'お問い合わせ内容の確認';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title><?= h($page_title) ?> ｜ マインドフリー神道学アカデミア</title>
  <link rel="stylesheet" href="./form.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;600&family=Noto+Serif+JP:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>

  <header class="site-header">
    <a href="https://okage-san.com/">マインドフリー神道学アカデミア</a>
    <div class="site-header-sub">岡慶行 公式Webサイト</div>
  </header>

  <main class="form-container">
    <div class="form-title-area">
      <span class="kicker">MIND FREE SHINTO</span>
      <h1 class="form-title"><?= h($page_title) ?></h1>
      <p class="form-lede">
        <?= $show_edit
            ? '内容をご確認のうえ、修正して「確認画面へ」をお進みください。'
            : 'ご入力内容をご確認のうえ、問題がなければ「送信する」ボタンを押してください。' ?>
      </p>
    </div>

    <!-- ステップナビ -->
    <div class="step-bar">
      <div class="step-item<?= $show_edit ? ' is-active' : '' ?>"><span class="step-num">1</span> 入力</div>
      <div class="step-item<?= $show_edit ? '' : ' is-active' ?>"><span class="step-num">2</span> 確認</div>
      <div class="step-item"><span class="step-num">3</span> 完了</div>
    </div>

<?php if (isset($fatal)): ?>
    <div class="form-box">
      <p class="form-error" style="margin:0;"><?= h($fatal) ?></p>
    </div>
<?php endif; ?>

<?php if ($show_edit): ?>
    <!-- ══ 編集画面 ══ -->
    <form action="./contact.php" method="POST">
      <input type="hidden" name="step" value="confirm">
      <div style="position:absolute; left:-9999px;" aria-hidden="true">
        <label>Website<input type="text" name="hp_website" tabindex="-1" autocomplete="off"></label>
      </div>

      <div class="form-box">
        <div class="form-group">
          <label class="form-label" for="name">お名前（漢字） <span class="badge-req">必須</span></label>
          <input type="text" id="name" name="name" value="<?= h($data['name']) ?>"
                 placeholder="例：山田 太郎"
                 class="form-control<?= isset($errors['name']) ? ' input-error' : '' ?>">
          <?php if (isset($errors['name'])): ?><p class="form-error"><?= h($errors['name']) ?></p><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" for="kana">ふりがな <span class="badge-opt">任意</span></label>
          <input type="text" id="kana" name="kana" value="<?= h($data['kana']) ?>"
                 placeholder="例：やまだ たろう"
                 class="form-control<?= isset($errors['kana']) ? ' input-error' : '' ?>">
          <?php if (isset($errors['kana'])): ?><p class="form-error"><?= h($errors['kana']) ?></p><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">メールアドレス <span class="badge-req">必須</span></label>
          <input type="email" id="email" name="email" value="<?= h($data['email']) ?>"
                 placeholder="例：yamada@example.com"
                 class="form-control<?= isset($errors['email']) ? ' input-error' : '' ?>">
          <?php if (isset($errors['email'])): ?><p class="form-error"><?= h($errors['email']) ?></p><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" for="category">お問い合わせの種類 <span class="badge-req">必須</span></label>
          <select id="category" name="category"
                  class="form-select<?= isset($errors['category']) ? ' input-error' : '' ?>">
            <option value="" <?= $data['category'] === '' ? 'selected' : '' ?>>選択してください</option>
<?php foreach (CATEGORIES as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= $data['category'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
<?php endforeach; ?>
          </select>
          <?php if (isset($errors['category'])): ?><p class="form-error"><?= h($errors['category']) ?></p><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" for="message">お問い合わせ内容 <span class="badge-req">必須</span></label>
          <textarea id="message" name="message" rows="8"
                    placeholder="ご質問やご相談内容を具体的にご記入ください。"
                    class="form-textarea<?= isset($errors['message']) ? ' input-error' : '' ?>"><?= h($data['message']) ?></textarea>
          <?php if (isset($errors['message'])): ?><p class="form-error"><?= h($errors['message']) ?></p><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="checkbox-label">
            <input type="checkbox" name="privacy" value="1" checked>
            <span><a href="<?= h($BASE) ?>/privacy-policy/" target="_blank" rel="noopener">プライバシーポリシー</a> に同意する</span>
          </label>
          <?php if (isset($errors['privacy'])): ?><p class="form-error"><?= h($errors['privacy']) ?></p><?php endif; ?>
        </div>
      </div>

      <div class="form-actions">
        <a href="<?= h($BASE) ?>/contact/" class="btn btn-ghost">◀ 戻る</a>
        <button type="submit" class="btn btn-gold">確認画面へ ➔</button>
      </div>
    </form>

<?php else: ?>
    <!-- ══ 確認画面 ══ -->
    <form action="./contact.php" method="POST">
      <input type="hidden" name="step" value="send">
      <input type="hidden" name="csrf_token" value="<?= h($_SESSION['contact_csrf']) ?>">

      <div class="form-box">
        <table class="confirm-table">
          <tr>
            <th>お名前</th>
            <td><?= h($data['name']) ?> 様</td>
          </tr>
          <tr>
            <th>ふりがな</th>
            <td><?= $data['kana'] !== '' ? h($data['kana']) : '（未記入）' ?></td>
          </tr>
          <tr>
            <th>メールアドレス</th>
            <td><?= h($data['email']) ?></td>
          </tr>
          <tr>
            <th>お問い合わせの種類</th>
            <td><?= h(mfs_category_label($data['category'])) ?></td>
          </tr>
          <tr>
            <th>お問い合わせ内容</th>
            <td><?= nl2br(h($data['message'])) ?></td>
          </tr>
        </table>
      </div>

      <div class="form-actions">
        <button type="submit" form="edit-back" class="btn btn-ghost">◀ 修正する</button>
        <button type="submit" class="btn btn-gold">この内容で送信する ➔</button>
      </div>
    </form>

    <!-- 「修正する」用。入力値はセッションに入っているのでstepだけ送ります。 -->
    <form id="edit-back" action="./contact.php" method="POST" hidden>
      <input type="hidden" name="step" value="edit">
    </form>
<?php endif; ?>
  </main>

  <footer class="site-footer">
    &copy; マインドフリー神道学アカデミア All Rights Reserved.
  </footer>

</body>
</html>
