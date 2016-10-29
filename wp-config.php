<?php
/**
 * WordPress için taban ayar dosyası.
 *
 * Bu dosya şu ayarları içerir: MySQL ayarları, tablo öneki,
 * gizli anahtaralr ve ABSPATH. Daha fazla bilgi için 
 * {@link https://codex.wordpress.org/Editing_wp-config.php wp-config.php düzenleme}
 * yardım sayfasına göz atabilirsiniz. MySQL ayarlarınızı servis sağlayıcınızdan edinebilirsiniz.
 *
 * Bu dosya kurulum sırasında wp-config.php dosyasının oluşturulabilmesi için
 * kullanılır. İsterseniz bu dosyayı kopyalayıp, ismini "wp-config.php" olarak değiştirip,
 * değerleri girerek de kullanabilirsiniz.
 *
 * @package WordPress
 */

if (strpos($_SERVER['SERVER_NAME'], "www.") === 0) {
	$domain = substr($_SERVER['SERVER_NAME'], 4, strlen($_SERVER['SERVER_NAME']));
	//header("Location: http://$domain");
} else {
	$domain = $_SERVER['SERVER_NAME'];
}

if (strpos($domain, '.local') !== FALSE) {
	define('ENV', 'development');
} else if(strpos($domain, 'demo.') !== FALSE) {
	define('ENV', 'testing');
} else {
	define('ENV', 'production');
}

// ** MySQL ayarları - Bu bilgileri sunucunuzdan alabilirsiniz ** //
/** WordPress için kullanılacak veritabanının adı */


/** MySQL veritabanı kullanıcısı */
define('DB_USER', 'srkn_mimozou');

/** MySQL veritabanı parolası */
if(ENV === 'testing') {
	define('DB_NAME', 'mimoza_db');
	define('DB_PASSWORD', 'srknkc31');
} else if(ENV === 'development') {
	define('DB_NAME', 'mimoza_db');
	define('DB_PASSWORD', '');
} else {
	define('DB_NAME', 'srkn_mimoza_db123');
	define('DB_PASSWORD', 'Xy2ntmWq');
}

/** MySQL sunucusu */
define('DB_HOST', 'localhost');

/** Yaratılacak tablolar için veritabanı karakter seti. */
define('DB_CHARSET', 'utf8mb4');

/** Veritabanı karşılaştırma tipi. Herhangi bir şüpheniz varsa bu değeri değiştirmeyin. */
define('DB_COLLATE', '');

/**#@+
 * Eşsiz doğrulama anahtarları.
 *
 * Her anahtar farklı bir karakter kümesi olmalı!
 * {@link http://api.wordpress.org/secret-key/1.1/salt WordPress.org secret-key service} servisini kullanarak yaratabilirsiniz.
 * Çerezleri geçersiz kılmak için istediğiniz zaman bu değerleri değiştirebilirsiniz. Bu tüm kullanıcıların tekrar giriş yapmasını gerektirecektir.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'qoN-F`-fknFyXU+K302#e|@[N!7YaiS8dl ^`),9O#vf_t}||gl(N<~jJ-BVE+l!');
define('SECURE_AUTH_KEY',  'H[K77`T543VycRx?iEH<#soSq0mgRvH3Vf-`S?t%-@vOD/?$el$&PNn1C}BY{Gw_');
define('LOGGED_IN_KEY',    '$?ZvL|o}-UVG4-zyE(Ds@S}25`6@qQsSqR6/A_W|9&*4.t4ug@$<S-d}}D ND{m<');
define('NONCE_KEY',        'Ggz|CTa*L}f>Z&kRW)3b,v57a*ewW0~)aO.!|+k.+ROTOn:(BOnn[awlDPGR1=gq');
define('AUTH_SALT',        'U-H/=oX10UjGwY|m80&J{T,.-~e5Y+~Ma9Fj;(agQ$UF142X_ Ep,ST}VMdJ)w9|');
define('SECURE_AUTH_SALT', 'v-WxT4C@TbW[M4-weSV)/y;O7R.VC||.x=fGZ[|,9e{S9={5*m).O#;:Pdk:L@[&');
define('LOGGED_IN_SALT',   'gwNVS)R6i?,n#3E3Mw+*!;6F;|yZb]WAd/Ar`N7,mQe]k,Hi2-0.OJg HVOHIu^O');
define('NONCE_SALT',       'F;B*8-HE_^#t~0 VKDbs|u;2Y$,>^4JW|zPi6lQp~$h@cqVHcI]iyka[>|,<;tmV');
/**#@-*/

/**
 * WordPress veritabanı tablo ön eki.
 *
 * Tüm kurulumlara ayrı bir önek vererek bir veritabanına birden fazla kurulum yapabilirsiniz.
 * Sadece rakamlar, harfler ve alt çizgi lütfen.
 */
$table_prefix  = 'wp_';

/**
 * Geliştiriciler için: WordPress hata ayıklama modu.
 *
 * Bu değeri "true" yaparak geliştirme sırasında hataların ekrana basılmasını sağlayabilirsiniz.
 * Tema ve eklenti geliştiricilerinin geliştirme aşamasında WP_DEBUG
 * kullanmalarını önemle tavsiye ederiz.
 */
define('WP_DEBUG', true); 

/* Hepsi bu kadar. Mutlu bloglamalar! */

/** WordPress dizini için mutlak yol. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** WordPress değişkenlerini ve yollarını kurar. */
require_once(ABSPATH . 'wp-settings.php');
