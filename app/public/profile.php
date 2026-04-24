<?php
$pageTitle = 'Profil Saya';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$uid   = $_SESSION['user_id'];
$error = $success = '';

$stmt = $conn->prepare("SELECT id,username,email,role,balance,bio,display_name,avatar,created_at FROM users WHERE id=?");
$stmt->bind_param("i",$uid);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';

    // Update profile info
    if ($action==='update_profile') {
        $dn  = trim($_POST['display_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        if (strlen($dn)>50) { $error='Display name maksimal 50 karakter.'; }
        else {
            $s=$conn->prepare("UPDATE users SET display_name=?,bio=? WHERE id=?");
            $s->bind_param("ssi",$dn,$bio,$uid);$s->execute();
            $success='Profil berhasil diperbarui.';
            $profile['display_name']=$dn; $profile['bio']=$bio;
        }
    }
    // Update avatar
    elseif ($action==='update_avatar') {
        if (empty($_FILES['avatar']['name'])) { $error='Pilih file foto.'; }
        else {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'],PATHINFO_EXTENSION));
            if (!in_array($ext,['jpg','jpeg','png','gif','webp'])) { $error='Format tidak didukung.'; }
            elseif ($_FILES['avatar']['size']>2*1024*1024) { $error='Maks 2MB.'; }
            else {
                $dir = __DIR__.'/../uploads/avatars/';
                if (!is_dir($dir)) mkdir($dir,0777,true);
                if (!empty($profile['avatar']) && $profile['avatar']!=='default_avatar.png') {
                    $old=$dir.$profile['avatar']; if(file_exists($old)) unlink($old);
                }
                $fn='avatar_'.$uid.'_'.uniqid().'.'.$ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'],$dir.$fn)) {
                    $s=$conn->prepare("UPDATE users SET avatar=? WHERE id=?");
                    $s->bind_param("si",$fn,$uid);$s->execute();
                    $success='Foto profil diperbarui.'; $profile['avatar']=$fn;
                } else { $error='Gagal upload foto.'; }
            }
        }
    }
    // Change password
    elseif ($action==='change_password') {
        $old=$_POST['old_password']??''; $new=$_POST['new_password']??''; $con=$_POST['confirm_password']??'';
        $s=$conn->prepare("SELECT password FROM users WHERE id=?");$s->bind_param("i",$uid);$s->execute();
        $hash=$s->get_result()->fetch_assoc()['password'];
        $ok=(password_verify($old,$hash)||md5($old)===$hash);
        if (!$ok) { $error='Password lama salah.'; }
        elseif (strlen($new)<6) { $error='Password baru minimal 6 karakter.'; }
        elseif ($new!==$con) { $error='Konfirmasi tidak cocok.'; }
        else {
            $h=password_hash($new,PASSWORD_DEFAULT);
            $s=$conn->prepare("UPDATE users SET password=? WHERE id=?");$s->bind_param("si",$h,$uid);$s->execute();
            $success='Password berhasil diubah.';
        }
    }
    // Delete account
    elseif ($action==='delete_account') {
        $cp=$_POST['delete_confirm_password']??'';
        $s=$conn->prepare("SELECT password FROM users WHERE id=?");$s->bind_param("i",$uid);$s->execute();
        $hash=$s->get_result()->fetch_assoc()['password'];
        $ok=(password_verify($cp,$hash)||md5($cp)===$hash);
        if (!$ok) { $error='Password salah. Akun tidak dihapus.'; }
        else {
            if (!empty($profile['avatar'])&&$profile['avatar']!=='default_avatar.png') {
                $ap=__DIR__.'/../uploads/avatars/'.$profile['avatar']; if(file_exists($ap)) unlink($ap);
            }
            $s=$conn->prepare("DELETE FROM users WHERE id=?");$s->bind_param("i",$uid);$s->execute();
            session_destroy(); header('Location:/public/login.php?msg=account_deleted'); exit;
        }
    }
}

$avatarFile = $profile['avatar'] ?? '';
$avatarSrc  = (!empty($avatarFile) && file_exists(__DIR__.'/../uploads/avatars/'.$avatarFile))
              ? '/uploads/avatars/'.$avatarFile : null;
$displayName= !empty($profile['display_name']) ? $profile['display_name'] : $profile['username'];
$initials   = strtoupper(substr($displayName,0,1));
$orderCount = $conn->query("SELECT COUNT(*) c FROM orders WHERE user_id=$uid")->fetch_assoc()['c'];
$sellCount  = $conn->query("SELECT COUNT(*) c FROM products WHERE seller_id=$uid")->fetch_assoc()['c'];

require_once __DIR__.'/../includes/header.php';
?>
<style>
.profile-hero{background:linear-gradient(135deg,rgba(0,212,255,.04),rgba(0,255,136,.03));border:1px solid var(--nh-border);border-radius:12px;padding:2rem;display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;}
.av-wrap{position:relative;flex-shrink:0;}
.av-img{width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--nh-primary-border);}
.av-init{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--nh-primary),var(--nh-green));display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#050505;}
.av-cam{position:absolute;bottom:0;right:0;width:26px;height:26px;background:var(--nh-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid var(--nh-bg);}
.av-cam i{font-size:.7rem;color:#050505;}
.tab-btn{background:transparent;border:none;color:var(--nh-text-secondary);font-size:.82rem;font-weight:500;padding:8px 16px;border-radius:6px;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:6px;width:100%;text-align:left;}
.tab-btn:hover{background:rgba(255,255,255,.04);color:var(--nh-text);}
.tab-btn.active{background:var(--nh-primary-dim);color:var(--nh-primary);}
.tab-panel{display:none;}.tab-panel.active{display:block;animation:fadeIn .25s ease;}
.stat-chip{background:rgba(255,255,255,.03);border:1px solid var(--nh-border);border-radius:6px;padding:.5rem 1rem;font-size:.78rem;text-align:center;}
.stat-chip .sv{font-family:'JetBrains Mono',monospace;color:var(--nh-primary);font-size:1rem;font-weight:700;display:block;}
.danger-zone{border:1px solid rgba(255,59,59,.15);border-radius:8px;padding:1.5rem;background:rgba(255,59,59,.03);}
</style>

<div class="d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-person-circle" style="color:var(--nh-primary);font-size:1.4rem;"></i>
  <h2 class="mb-0" style="font-weight:800;">Profil Saya</h2>
</div>

<?php if($error):?><div class="alert mb-3" style="background:rgba(255,59,59,.06);border:1px solid rgba(255,59,59,.2);color:var(--nh-danger);"><i class="bi bi-exclamation-triangle me-2"></i><?=htmlspecialchars($error)?></div><?php endif;?>
<?php if($success):?><div class="alert mb-3" style="background:rgba(0,255,136,.06);border:1px solid rgba(0,255,136,.2);color:var(--nh-green);"><i class="bi bi-check-circle me-2"></i><?=htmlspecialchars($success)?></div><?php endif;?>

<!-- Hero -->
<div class="profile-hero animate-in">
  <div class="av-wrap">
    <?php if($avatarSrc):?><img src="<?=$avatarSrc?>?t=<?=time()?>" class="av-img" alt="Avatar">
    <?php else:?><div class="av-init"><?=$initials?></div><?php endif;?>
    <label class="av-cam" for="quickAv" title="Ganti foto"><i class="bi bi-camera-fill"></i></label>
  </div>
  <div class="flex-grow-1">
    <h4 class="fw-bold mb-0" style="font-size:1.1rem;"><?=htmlspecialchars($displayName)?></h4>
    <div style="font-size:.75rem;color:var(--nh-text-muted);font-family:'JetBrains Mono',monospace;">
      @<?=htmlspecialchars($profile['username'])?> &nbsp;·&nbsp;
      <span style="color:<?=$profile['role']==='admin'?'var(--nh-warning)':'var(--nh-green)'?>;"><?=strtoupper($profile['role'])?></span>
    </div>
    <?php if(!empty($profile['bio'])):?><p style="font-size:.82rem;color:var(--nh-text-secondary);margin:.4rem 0 0;"><?=htmlspecialchars($profile['bio'])?></p><?php endif;?>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <div class="stat-chip"><span class="sv"><?=$orderCount?></span>Pesanan</div>
    <div class="stat-chip"><span class="sv"><?=$sellCount?></span>Produk</div>
    <div class="stat-chip"><span class="sv" style="color:var(--nh-green);font-size:.8rem;"><?=formatRupiah($profile['balance'])?></span>Saldo</div>
  </div>
</div>
<form method="POST" enctype="multipart/form-data" id="quickAvForm" style="display:none;">
  <input type="hidden" name="action" value="update_avatar">
  <input type="file" name="avatar" id="quickAv" accept=".jpg,.jpeg,.png,.gif,.webp" onchange="this.form.submit();">
</form>

<div class="row g-3">
  <!-- Sidebar -->
  <div class="col-md-3">
    <div class="card-nhsec p-2">
      <button class="tab-btn active" onclick="sw('info',this)"><i class="bi bi-person"></i> Info Profil</button>
      <button class="tab-btn" onclick="sw('avatar',this)"><i class="bi bi-camera"></i> Foto Profil</button>
      <button class="tab-btn" onclick="sw('password',this)"><i class="bi bi-shield-lock"></i> Password</button>
      <hr style="border-color:var(--nh-border);margin:6px 0;">
      <button class="tab-btn" onclick="sw('danger',this)" style="color:var(--nh-danger);"><i class="bi bi-trash"></i> Hapus Akun</button>
    </div>
  </div>

  <!-- Panels -->
  <div class="col-md-9">
    <!-- Info -->
    <div class="tab-panel active card-nhsec p-4" id="tab-info">
      <p class="form-label mb-3">INFO PROFIL</p>
      <form method="POST">
        <input type="hidden" name="action" value="update_profile">
        <div class="mb-3">
          <label class="form-label">DISPLAY NAME</label>
          <input type="text" name="display_name" class="form-control" maxlength="50"
                 value="<?=htmlspecialchars($profile['display_name']??'')?>" placeholder="Nama publik...">
          <div style="font-size:.72rem;color:#444;margin-top:4px;">Kosongkan = pakai username.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">EMAIL</label>
          <input class="form-control" value="<?=htmlspecialchars($profile['email'])?>" disabled style="opacity:.4;">
        </div>
        <div class="mb-3">
          <label class="form-label">BIO</label>
          <textarea name="bio" class="form-control" rows="3" maxlength="300" placeholder="Ceritakan tentang kamu..."><?=htmlspecialchars($profile['bio']??'')?></textarea>
          <div style="font-size:.72rem;color:#444;margin-top:4px;">Maks. 300 karakter.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">BERGABUNG SEJAK</label>
          <input class="form-control" value="<?=date('d M Y',strtotime($profile['created_at']))?>" disabled style="opacity:.4;">
        </div>
        <button type="submit" class="btn btn-nhsec"><i class="bi bi-check2"></i> Simpan</button>
      </form>
    </div>

    <!-- Avatar -->
    <div class="tab-panel card-nhsec p-4" id="tab-avatar">
      <p class="form-label mb-3">FOTO PROFIL</p>
      <div class="d-flex align-items-center gap-4 mb-4">
        <?php if($avatarSrc):?><img src="<?=$avatarSrc?>?t=<?=time()?>" id="avPrev" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:2px solid var(--nh-primary-border);">
        <?php else:?>
          <div id="avInitPrev" style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,var(--nh-primary),var(--nh-green));display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:800;color:#050505;"><?=$initials?></div>
          <img id="avPrev" src="" style="display:none;width:100px;height:100px;border-radius:50%;object-fit:cover;border:2px solid var(--nh-primary-border);">
        <?php endif;?>
        <div style="font-size:.82rem;color:var(--nh-text-secondary);">Format: JPG, PNG, GIF, WEBP · Maks. 2MB</div>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_avatar">
        <div class="mb-3">
          <label class="form-label">PILIH FOTO BARU</label>
          <input type="file" name="avatar" id="avInput" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp" onchange="prevAv(this)">
        </div>
        <button type="submit" class="btn btn-nhsec"><i class="bi bi-upload"></i> Upload Foto</button>
      </form>
    </div>

    <!-- Password -->
    <div class="tab-panel card-nhsec p-4" id="tab-password">
      <p class="form-label mb-3">GANTI PASSWORD</p>
      <form method="POST" autocomplete="off">
        <input type="hidden" name="action" value="change_password">
        <div class="mb-3">
          <label class="form-label">PASSWORD LAMA</label>
          <div class="input-group">
            <input type="password" name="old_password" id="p1" class="form-control" required placeholder="Password saat ini...">
            <button type="button" class="input-group-text" onclick="tp('p1',this)" style="cursor:pointer;"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">PASSWORD BARU</label>
          <div class="input-group">
            <input type="password" name="new_password" id="p2" class="form-control" required placeholder="Min. 6 karakter..." oninput="chkStr(this.value)">
            <button type="button" class="input-group-text" onclick="tp('p2',this)" style="cursor:pointer;"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <div class="mb-1">
          <div style="height:3px;background:var(--nh-border);border-radius:2px;overflow:hidden;">
            <div id="sBar" style="height:100%;width:0%;transition:width .3s,background .3s;border-radius:2px;"></div>
          </div>
          <div id="sTxt" style="font-size:.7rem;color:#444;margin-top:3px;font-family:'JetBrains Mono',monospace;"></div>
        </div>
        <div class="mb-3">
          <label class="form-label">KONFIRMASI PASSWORD BARU</label>
          <div class="input-group">
            <input type="password" name="confirm_password" id="p3" class="form-control" required placeholder="Ulangi password baru...">
            <button type="button" class="input-group-text" onclick="tp('p3',this)" style="cursor:pointer;"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="btn btn-nhsec"><i class="bi bi-shield-check"></i> Ubah Password</button>
      </form>
    </div>

    <!-- Danger -->
    <div class="tab-panel card-nhsec p-4" id="tab-danger">
      <p class="form-label mb-3" style="color:var(--nh-danger);">HAPUS AKUN</p>
      <div class="danger-zone">
        <p style="font-size:.85rem;color:var(--nh-text-secondary);">
          <i class="bi bi-exclamation-triangle" style="color:var(--nh-danger);"></i>
          Tindakan ini <strong style="color:var(--nh-danger);">tidak dapat dibatalkan</strong>.
          Semua data akan dihapus permanen.
        </p>
        <form method="POST" onsubmit="return confirm('Hapus akun permanen?')">
          <input type="hidden" name="action" value="delete_account">
          <div class="mb-3">
            <label class="form-label" style="color:var(--nh-danger);">KONFIRMASI PASSWORD</label>
            <input type="password" name="delete_confirm_password" class="form-control" required
                   placeholder="Masukkan password..." style="border-color:rgba(255,59,59,.25)!important;">
          </div>
          <button type="submit" class="btn" style="background:var(--nh-danger);color:#fff;border:none;padding:8px 18px;border-radius:4px;font-weight:600;font-size:.82rem;">
            <i class="bi bi-trash3"></i> Hapus Akun Permanen
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function sw(n,b){document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));document.querySelectorAll('.tab-btn').forEach(x=>x.classList.remove('active'));document.getElementById('tab-'+n).classList.add('active');b.classList.add('active');}
function prevAv(i){if(i.files&&i.files[0]){const r=new FileReader();r.onload=e=>{const p=document.getElementById('avPrev');p.src=e.target.result;p.style.display='block';const x=document.getElementById('avInitPrev');if(x)x.style.display='none';};r.readAsDataURL(i.files[0]);}}
function tp(id,btn){const i=document.getElementById(id),ic=btn.querySelector('i');i.type=i.type==='password'?'text':'password';ic.className=i.type==='password'?'bi bi-eye':'bi bi-eye-slash';}
function chkStr(v){const b=document.getElementById('sBar'),t=document.getElementById('sTxt');let s=0;if(v.length>=6)s++;if(v.length>=10)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;const lvl=[{w:'20%',c:'#ff3b3b',l:'Sangat lemah'},{w:'40%',c:'#ff6b2b',l:'Lemah'},{w:'60%',c:'#ffb800',l:'Cukup'},{w:'80%',c:'#00d4ff',l:'Kuat'},{w:'100%',c:'#00ff88',l:'Sangat kuat'}];const lv=lvl[Math.max(0,s-1)];b.style.width=v.length?lv.w:'0%';b.style.background=lv.c;t.textContent=v.length?lv.l:'';t.style.color=lv.c;}
</script>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
