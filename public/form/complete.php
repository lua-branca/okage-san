<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./');
    exit;
}

// POSTデータを優先取得、フォールバックとしてセッション
$data = [
    'tour_id'           => trim($_POST['tour_id'] ?? $_SESSION['form_data']['tour_id'] ?? ''),
    'tour_name'         => trim($_POST['tour_name'] ?? $_SESSION['form_data']['tour_name'] ?? ''),
    'name'              => trim($_POST['name'] ?? $_SESSION['form_data']['name'] ?? ''),
    'furigana'          => trim($_POST['furigana'] ?? $_SESSION['form_data']['furigana'] ?? ''),
    'email'             => trim($_POST['email'] ?? $_SESSION['form_data']['email'] ?? ''),
    'tel'               => trim($_POST['tel'] ?? $_SESSION['form_data']['tel'] ?? ''),
    'pref'              => trim($_POST['pref'] ?? $_SESSION['form_data']['pref'] ?? ''),
    'referral_source'   => trim($_POST['referral_source'] ?? $_SESSION['form_data']['referral_source'] ?? ''),
    'emergency_contact' => trim($_POST['emergency_contact'] ?? $_SESSION['form_data']['emergency_contact'] ?? ''),
    'receipt_option'    => trim($_POST['receipt_option'] ?? $_SESSION['form_data']['receipt_option'] ?? '不要'),
    'message'           => trim($_POST['message'] ?? $_SESSION['form_data']['message'] ?? ''),
];

if (empty($data['name']) || empty($data['email'])) {
    $_SESSION['form_errors'] = ['system' => 'お申し込みデータが不足しています。恐れ入りますがもう一度ご入力ください。'];
    header('Location: ./');
    exit;
}

// 設定ファイルの読み込み
$config_path_upper = __DIR__ . '/../../config.php';
$config_path_local = __DIR__ . '/config.php';

$gas_url = '';
// 🔴 ここに実際のトークンを書かないこと。このリポジトリは PUBLIC です。
//    値は config.php（コミット対象外）からのみ読み込みます。
//    設定が無いときは空のまま＝GAS側の認証で弾かれます（黙って通さない）。
$secret_token = '';

if (file_exists($config_path_upper)) {
    $config = require $config_path_upper;
    if (is_array($config)) {
        $gas_url = $config['GAS_WEB_APP_URL'] ?? '';
        $secret_token = $config['SECRET_TOKEN'] ?? $secret_token;
    }
}

if (empty($gas_url) && file_exists($config_path_local)) {
    $config_local = require $config_path_local;
    if (is_array($config_local)) {
        $gas_url = $config_local['GAS_WEB_APP_URL'] ?? '';
        $secret_token = $config_local['SECRET_TOKEN'] ?? $secret_token;
    } elseif (defined('GAS_WEB_APP_URL')) {
        $gas_url = GAS_WEB_APP_URL;
    }
    if (defined('GAS_SECRET_TOKEN')) {
        $secret_token = GAS_SECRET_TOKEN;
    }
}

$tour_id = $data['tour_id'] ?? '';

// 決済URLのフォールバック用マッピング
// ※ 正本は「02_商品・サービスマスタ」N列。ここはGASが落ちたときの保険としてのみ使う
$payment_urls_fallback = [
    'session-hypno'     => 'https://univa.cc/i0O5-W',
    '2026-08-09-satori' => 'https://univa.cc/OdFce1',
];

// ログ出力先：公開ディレクトリの外（URLで読まれないようにするため）
// 個人情報（氏名・メール・電話）は書かない。成否と申込IDだけ残す
$log_dir = __DIR__ . '/../../mfs_logs';
function mfs_log($dir, $message) {
    if (!is_dir($dir)) { @mkdir($dir, 0700, true); }
    @file_put_contents($dir . '/gas.log', date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND);
}

// 1. 先にGASへ送信し、申込IDと決済URLを受け取る
//    （決済URLをメールに載せるため、メール送信より前に実行する）
$app_id = '';
$univapay_url = '';

if (!empty($gas_url)) {
    $payload = json_encode([
        'token'  => $secret_token,
        'action' => 'add_application',
        'data'   => $data,
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $gas_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode($res, true);
    if (is_array($decoded) && ($decoded['status'] ?? '') === 'success') {
        $app_id       = $decoded['data']['appId'] ?? '';
        $univapay_url = $decoded['data']['paymentUrl'] ?? '';
        mfs_log($log_dir, "OK  tour_id={$tour_id} appId={$app_id}");
    } else {
        mfs_log($log_dir, "NG  tour_id={$tour_id} curl_err={$err} res=" . substr((string)$res, 0, 300));
    }
}

// GASから決済URLを取得できなかった場合はフォールバック
if (empty($univapay_url)) {
    $univapay_url = $payment_urls_fallback[$tour_id] ?? 'https://univa.cc/OdFce1';
}

// 2. PHP側で自動返信メールをUTF-8で文字化けゼロ送信
$from_email = "support@okage-san.com";
$from_name  = "マインドフリー神道学アカデミア";
$tour_title = !empty($data['tour_name']) ? $data['tour_name'] : 'お申し込み';

// メール件名
$subject = "【マインドフリー神道学アカデミア】お申し込みありがとうございます";

// メール本文
$mail_body = "{$data['name']} 様\n\n";
$mail_body .= "このたびは「{$tour_title}」にお申し込みいただき、誠にありがとうございます。\n";
$mail_body .= "下記の内容でお申し込みを承りました。\n\n";
$mail_body .= "----------------------------------------\n";
$mail_body .= "■ お申し込み内容: {$tour_title}\n";
if (!empty($data['email'])) $mail_body .= "■ メールアドレス: {$data['email']}\n";
if (!empty($data['tel']))   $mail_body .= "■ 電話番号: {$data['tel']}\n";
if (!empty($data['pref']))  $mail_body .= "■ 都道府県: {$data['pref']}\n";
if (!empty($data['message'])) {
    $mail_body .= "■ 備考・メッセージ:\n{$data['message']}\n";
}
$mail_body .= "----------------------------------------\n\n";

$mail_body .= "【お支払いについて】\n";
$mail_body .= "お支払いは「クレジットカード」または「銀行振込」にて承っております。\n\n";

$mail_body .= "■ クレジットカード決済をご希望の方\n";
$mail_body .= "下記URLより決済画面にお進みいただき、お手続きを完了させてください。\n\n";
$mail_body .= "  ▼ クレジットカード決済URL\n";
$mail_body .= "  {$univapay_url}\n\n";

$mail_body .= "■ 銀行振込をご希望の方\n";
$mail_body .= "下記口座へのお振込をお願いいたします。\n\n";
$mail_body .= "  金融機関: ドコモＳＭＴＢネット銀行（金融機関コード 0038）\n";
$mail_body .= "  支店名: リンゴ支店（支店コード 105）\n";
$mail_body .= "  口座種別: 普通\n";
$mail_body .= "  口座番号: 9052668\n";
$mail_body .= "  口座名義: オカ　ヨシユキ\n";
$mail_body .= "  ※ 振込名義がお申込者と異なる場合は、事前に本メールへの返信にてお知らせください。\n\n";

$mail_body .= "ご不明な点やご質問がございましたら、このメールに直接ご返信ください。\n\n";
$mail_body .= "感謝してます。\n\n";
$mail_body .= "岡 慶行\n";
$mail_body .= "マインドフリー神道学アカデミア\n";
$mail_body .= "https://okage-san.com/";

// UTF-8ヘッダー＆エンコーディング（文字化け完全対策）
$encoded_from_name = "=?UTF-8?B?" . base64_encode($from_name) . "?=";
$encoded_subject   = "=?UTF-8?B?" . base64_encode($subject) . "?=";
$encoded_admin_sub = "=?UTF-8?B?" . base64_encode("[管理者通知] " . $subject) . "?=";

$headers = [];
$headers[] = "From: {$encoded_from_name} <{$from_email}>";
$headers[] = "Reply-To: {$from_email}";
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/plain; charset=UTF-8";
$headers[] = "Content-Transfer-Encoding: 8bit";
$headers_str = implode("\r\n", $headers);

// 申込者宛送信
@mail($data['email'], $encoded_subject, $mail_body, $headers_str);

// 管理者通知（support@okage-san.com）
@mail($from_email, $encoded_admin_sub, "【管理者通知】以下の内容でお申し込みがありました。\n\n" . $mail_body, $headers_str);

// セッションクリア
unset($_SESSION['form_data']);
unset($_SESSION['form_errors']);
unset($_SESSION['csrf_token']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>お申し込み完了 ｜ マインドフリー神道学アカデミア</title>
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
    <!-- ステップナビ -->
    <div class="step-bar">
      <div class="step-item"><span class="step-num">1</span> 入力</div>
      <div class="step-item"><span class="step-num">2</span> 確認</div>
      <div class="step-item is-active"><span class="step-num">3</span> 完了</div>
    </div>

    <div class="form-box complete-card">
      <div class="complete-icon">✦</div>
      <h1 class="form-title">お申し込みを受け付けました</h1>
      <p class="form-lede">
        この度はお申し込みいただき、誠にありがとうございます。<br>
        ご入力いただきましたメールアドレス宛に、受付確認メールをお送りいたしました。
      </p>

      <!-- クレジットカード決済案内ボックス（上） -->
      <div class="bank-info-box" style="border-style: solid; border-color: var(--asagi); margin-top: 2rem;">
        <div class="bank-info-title" style="color: var(--asagi); border-bottom-color: rgba(144, 208, 208, 0.3);">💳 クレジットカード決済をご希望の方</div>
        <p style="font-size: 0.875rem; color: #EDEAE4; margin: 0 0 1rem; line-height: 1.7;">
          クレジットカードでの決済をご希望の方は、下記ボタンより決済画面へお進みください。
        </p>
        <div style="text-align: center; margin: 0.5rem 0;">
          <a href="<?= htmlspecialchars($univapay_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ghost" style="width: 100%; max-width: 320px; box-sizing: border-box;">
            クレジットカードで支払う ➔
          </a>
        </div>
      </div>

      <!-- 振込先案内ボックス（下） -->
      <div class="bank-info-box">
        <div class="bank-info-title">■ 銀行振込をご希望の方（ご案内）</div>
        <p style="font-size: 0.875rem; color: #EDEAE4; margin: 0 0 0.8rem; line-height: 1.7;">
          下記口座へのお振込をお願いいたします。
        </p>
        <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
          <tr>
            <td style="color: var(--gold); padding: 0.3rem 0; width: 32%;">金融機関</td>
            <td style="color: var(--white); padding: 0.3rem 0;">ドコモＳＭＴＢネット銀行（金融機関コード 0038）</td>
          </tr>
          <tr>
            <td style="color: var(--gold); padding: 0.3rem 0;">支店名</td>
            <td style="color: var(--white); padding: 0.3rem 0;">リンゴ支店（支店コード 105）</td>
          </tr>
          <tr>
            <td style="color: var(--gold); padding: 0.3rem 0;">口座種別</td>
            <td style="color: var(--white); padding: 0.3rem 0;">普通</td>
          </tr>
          <tr>
            <td style="color: var(--gold); padding: 0.3rem 0;">口座番号</td>
            <td style="color: var(--white); padding: 0.3rem 0; font-weight: bold; letter-spacing: 0.05em;">9052668</td>
          </tr>
          <tr>
            <td style="color: var(--gold); padding: 0.3rem 0;">口座名義</td>
            <td style="color: var(--white); padding: 0.3rem 0;">オカ　ヨシユキ</td>
          </tr>
        </table>
        <p style="font-size: 0.8rem; color: var(--p200); margin: 0.8rem 0 0; line-height: 1.6;">
          ※ 振込名義がお申込者と異なる場合は、事前にお知らせください。
        </p>
      </div>

      <div style="margin-top: 2.5rem;">
        <a href="https://okage-san.com/" class="btn btn-gold">トップページへ戻る</a>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    &copy; マインドフリー神道学アカデミア All Rights Reserved.
  </footer>

</body>
</html>
