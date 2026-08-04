<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./');
    exit;
}

// スパムボット判定（ハニーポットに入力があれば即拒否）
if (!empty($_POST['hp_website'])) {
    exit('Spam detected.');
}

// データ受け取り & トリム
$data = [
    'tour_id'           => trim($_POST['tour_id'] ?? ''),
    'tour_name'         => trim($_POST['tour_name'] ?? ''),
    'name'              => trim($_POST['name'] ?? ''),
    'furigana'          => trim($_POST['furigana'] ?? ''),
    'email'             => trim($_POST['email'] ?? ''),
    'tel'               => trim($_POST['tel'] ?? ''),
    'pref'              => trim($_POST['pref'] ?? ''),
    'referral_source'   => trim($_POST['referral_source'] ?? ''),
    'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
    'receipt_option'    => trim($_POST['receipt_option'] ?? '不要'),
    'message'           => trim($_POST['message'] ?? ''),
];

$_SESSION['form_data'] = $data;
$errors = [];

// バリデーション（必須項目）
if (empty($data['name'])) {
    $errors['name'] = 'お名前を入力してください。';
}
if (empty($data['furigana'])) {
    $errors['furigana'] = 'フリガナを入力してください。';
}
if (empty($data['email'])) {
    $errors['email'] = 'メールアドレスを入力してください。';
} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = '正しいメールアドレスの形式で入力してください。';
}
if (empty($data['tel'])) {
    $errors['tel'] = '電話番号を入力してください。';
}
if (empty($data['pref'])) {
    $errors['pref'] = '都道府県を選択してください。';
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    header('Location: ./');
    exit;
}

// エラーがなければエラークリア
unset($_SESSION['form_errors']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>お申し込み内容の確認 ｜ マインドフリー神道学アカデミア</title>
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
      <h1 class="form-title">お申し込み内容の確認</h1>
      <p class="form-lede">ご入力内容をご確認のうえ、問題がなければ「送信する」ボタンを押してください。</p>
    </div>

    <!-- ステップナビ -->
    <div class="step-bar">
      <div class="step-item"><span class="step-num">1</span> 入力</div>
      <div class="step-item is-active"><span class="step-num">2</span> 確認</div>
      <div class="step-item"><span class="step-num">3</span> 完了</div>
    </div>

    <form action="./complete.php" method="POST">
      <!-- セッション切れ防止のためPOSTで次画面へデータ一式を引き継ぐ -->
      <input type="hidden" name="tour_id" value="<?= htmlspecialchars($data['tour_id'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="tour_name" value="<?= htmlspecialchars($data['tour_name'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="name" value="<?= htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="furigana" value="<?= htmlspecialchars($data['furigana'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="email" value="<?= htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="tel" value="<?= htmlspecialchars($data['tel'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="pref" value="<?= htmlspecialchars($data['pref'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="referral_source" value="<?= htmlspecialchars($data['referral_source'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="emergency_contact" value="<?= htmlspecialchars($data['emergency_contact'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="receipt_option" value="<?= htmlspecialchars($data['receipt_option'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="message" value="<?= htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8') ?>">

      <div class="form-box">
        <table class="confirm-table">
          <tr>
            <th>お申し込み内容</th>
            <td><?= htmlspecialchars($data['tour_name'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <tr>
            <th>お名前</th>
            <td><?= htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <tr>
            <th>フリガナ</th>
            <td><?= htmlspecialchars($data['furigana'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <tr>
            <th>メールアドレス</th>
            <td><?= htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <tr>
            <th>電話番号</th>
            <td><?= htmlspecialchars($data['tel'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <tr>
            <th>都道府県</th>
            <td><?= htmlspecialchars($data['pref'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <tr>
            <th>知ったきっかけ</th>
            <td><?= !empty($data['referral_source']) ? htmlspecialchars($data['referral_source'], ENT_QUOTES, 'UTF-8') : '（未選択）' ?></td>
          </tr>
          <tr>
            <th>緊急連絡先</th>
            <td><?= !empty($data['emergency_contact']) ? htmlspecialchars($data['emergency_contact'], ENT_QUOTES, 'UTF-8') : '（未記入）' ?></td>
          </tr>
          <tr>
            <th>備考・メッセージ</th>
            <td><?= !empty($data['message']) ? nl2br(htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8')) : '（なし）' ?></td>
          </tr>
        </table>
      </div>

      <div class="form-actions">
        <a href="./" class="btn btn-ghost">◀ 修正する</a>
        <button type="submit" class="btn btn-gold">この内容で送信する ➔</button>
      </div>
    </form>
  </main>

  <footer class="site-footer">
    &copy; マインドフリー神道学アカデミア All Rights Reserved.
  </footer>

</body>
</html>
