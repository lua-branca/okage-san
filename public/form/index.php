<?php
session_start();

// クエリパラメータまたはセッションから初期値取得
$tour_id = isset($_GET['tour_id']) ? htmlspecialchars($_GET['tour_id'], ENT_QUOTES, 'UTF-8') : ($_SESSION['form_data']['tour_id'] ?? '2026-08-09-satori');
$tour_name = isset($_GET['tour_name']) ? htmlspecialchars($_GET['tour_name'], ENT_QUOTES, 'UTF-8') : ($_SESSION['form_data']['tour_name'] ?? '悟りを開くまで帰れま10（8/9-8/11） 135,000円');

$data = $_SESSION['form_data'] ?? [];
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// CSRFトークン生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>お申し込み ｜ マインドフリー神道学アカデミア</title>
  <link rel="stylesheet" href="./form.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;600&family=Noto+Serif+JP:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>

  <!-- ヘッダー -->
  <header class="site-header">
    <a href="https://okage-san.com/">マインドフリー神道学アカデミア</a>
    <div class="site-header-sub">岡慶行 公式Webサイト</div>
  </header>

  <main class="form-container">
    <div class="form-title-area">
      <span class="kicker">MIND FREE SHINTO</span>
      <h1 class="form-title">お申し込みフォーム</h1>
      <p class="form-lede">下記フォームに必要事項をご入力のうえ、「確認画面へ進む」ボタンを押してください。</p>
    </div>

    <!-- ステップナビ -->
    <div class="step-bar">
      <div class="step-item is-active"><span class="step-num">1</span> 入力</div>
      <div class="step-item"><span class="step-num">2</span> 確認</div>
      <div class="step-item"><span class="step-num">3</span> 完了</div>
    </div>

    <?php if (!empty($errors['system'])): ?>
      <div class="form-box" style="border-color: var(--verm-light); background-color: rgba(158, 59, 36, 0.15);">
        <p style="color: var(--verm-light); margin: 0; font-size: 0.9rem;"><?= htmlspecialchars($errors['system'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    <?php endif; ?>

    <form action="./confirm.php" method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="tour_id" value="<?= htmlspecialchars($tour_id, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="tour_name" value="<?= htmlspecialchars($tour_name, ENT_QUOTES, 'UTF-8') ?>">

      <!-- スパムボット対策用ハニーポット（人間には非表示） -->
      <div style="display:none !important;" aria-hidden="true">
        <input type="text" name="hp_website" value="" tabindex="-1" autocomplete="off">
      </div>

      <div class="form-box">
        <!-- プログラム / サービス情報 -->
        <div class="form-group">
          <label class="form-label">お申し込み内容</label>
          <div style="font-size: 1.05rem; color: var(--gold); font-family: var(--mincho); padding: 0.5rem 0;">
            <?= htmlspecialchars($tour_name, ENT_QUOTES, 'UTF-8') ?>
          </div>
        </div>

        <!-- お名前 -->
        <div class="form-group">
          <label for="name" class="form-label">お名前（氏名）<span class="badge-req">必須</span></label>
          <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'input-error' : '' ?>" placeholder="山田 太郎" value="<?= htmlspecialchars($data['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          <?php if (isset($errors['name'])): ?><div class="form-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>

        <!-- フリガナ -->
        <div class="form-group">
          <label for="furigana" class="form-label">フリガナ<span class="badge-req">必須</span></label>
          <input type="text" id="furigana" name="furigana" class="form-control <?= isset($errors['furigana']) ? 'input-error' : '' ?>" placeholder="ヤマダ タロウ" value="<?= htmlspecialchars($data['furigana'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          <?php if (isset($errors['furigana'])): ?><div class="form-error"><?= htmlspecialchars($errors['furigana'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>

        <!-- メールアドレス -->
        <div class="form-group">
          <label for="email" class="form-label">メールアドレス<span class="badge-req">必須</span></label>
          <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'input-error' : '' ?>" placeholder="example@domain.com" value="<?= htmlspecialchars($data['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          <div class="form-help">※ お申し込み確定メール・ご案内をお送りします。PCメールアドレスを推奨いたします。</div>
          <?php if (isset($errors['email'])): ?><div class="form-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>

        <!-- 電話番号 -->
        <div class="form-group">
          <label for="tel" class="form-label">電話番号（携帯電話等）<span class="badge-req">必須</span></label>
          <input type="tel" id="tel" name="tel" class="form-control <?= isset($errors['tel']) ? 'input-error' : '' ?>" placeholder="090-1234-5678" value="<?= htmlspecialchars($data['tel'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          <div class="form-help">※ 当日の連絡用として使用いたします。</div>
          <?php if (isset($errors['tel'])): ?><div class="form-error"><?= htmlspecialchars($errors['tel'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>

        <!-- 都道府県 -->
        <div class="form-group">
          <label for="pref" class="form-label">お住まいの都道府県<span class="badge-req">必須</span></label>
          <select id="pref" name="pref" class="form-select <?= isset($errors['pref']) ? 'input-error' : '' ?>" required>
            <option value="">選択してください</option>
            <?php
            $prefs = ["北海道","青森県","岩手県","宮城県","秋田県","山形県","福島県","茨城県","栃木県","群馬県","埼玉県","千葉県","東京都","神奈川県","新潟県","富山県","石川県","福井県","山梨県","長野県","岐阜県","静岡県","愛知県","三重県","滋賀県","京都府","大阪府","兵庫県","奈良県","和歌山県","鳥取県","島根県","岡山県","広島県","山口県","徳島県","香川県","愛媛県","高知県","福岡県","佐賀県","長崎県","熊本県","大分県","宮崎県","鹿児島県","沖縄県","海外"];
            $current_pref = $data['pref'] ?? '';
            foreach ($prefs as $p) {
                $selected = ($current_pref === $p) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($p, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($p, ENT_QUOTES, 'UTF-8') . '</option>';
            }
            ?>
          </select>
          <?php if (isset($errors['pref'])): ?><div class="form-error"><?= htmlspecialchars($errors['pref'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>

        <!-- 岡慶行を知ったきっかけ（流入元） -->
        <!-- ※ 保存先は 01_顧客マスタ の「流入元」。新規のお客様のときだけ記録される（GAS側で制御） -->
        <div class="form-group">
          <label for="referral_source" class="form-label">岡慶行を知ったきっかけ<span class="badge-opt">任意</span></label>
          <select id="referral_source" name="referral_source" class="form-select">
            <option value="">選択してください</option>
            <?php
            $sources = ["書籍", "LINE", "Instagram", "Facebook", "X（旧Twitter）", "ご紹介", "検索", "イベント・セミナー", "その他"];
            $current_source = $data['referral_source'] ?? '';
            foreach ($sources as $s) {
                $selected = ($current_source === $s) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . '</option>';
            }
            ?>
          </select>
          <div class="form-help">※ はじめてお申し込みの方は、教えていただけると嬉しいです。今後のご案内の参考にさせていただきます。</div>
        </div>

        <!-- 緊急連絡先 -->
        <div class="form-group">
          <label for="emergency_contact" class="form-label">当日の緊急連絡先<span class="badge-opt">任意</span></label>
          <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" placeholder="例：090-0000-0000（配偶者携帯）" value="<?= htmlspecialchars($data['emergency_contact'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <div class="form-help">※ 万が一の緊急時に備え、連絡がつくご家族やご友人等の電話番号があればご記入ください。</div>
        </div>

        <!-- 領収書希望 -->
        <div class="form-group">
          <label class="form-label">領収書<span class="badge-opt">任意</span></label>
          <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem; font-size: 0.95rem;">
            <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; color: var(--w200);">
              <input type="radio" name="receipt_option" value="不要" <?= ($data['receipt_option'] ?? '不要') === '不要' ? 'checked' : '' ?>> 不要
            </label>
            <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; color: var(--gold);">
              <input type="radio" name="receipt_option" value="希望する" <?= ($data['receipt_option'] ?? '') === '希望する' ? 'checked' : '' ?>> 希望する
            </label>
          </div>
          <p style="font-size: 0.75rem; color: var(--p200); margin-top: 0.4rem; line-height: 1.5;">
            ※ 領収書をご希望の方は「希望する」を選択し、宛名・但し書きを下の備考欄にご記入ください。
          </p>
        </div>

        <!-- メッセージ・備考 -->
        <div class="form-group">
          <label for="message" class="form-label">備考・ご質問・メッセージ<span class="badge-opt">任意</span></label>
          <textarea id="message" name="message" class="form-textarea" rows="4" placeholder="※ 領収書をご希望の方は、こちらに宛名・但し書きをご記入ください。
※ 銀行振込の際にお申込者と異なる名義（会社名義等）で振込される場合や、ご質問などがあればご自由にご記入ください。"><?= htmlspecialchars($data['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-gold">確認画面へ進む ➔</button>
      </div>
    </form>
  </main>

  <footer class="site-footer">
    &copy; マインドフリー神道学アカデミア All Rights Reserved.
  </footer>

</body>
</html>
