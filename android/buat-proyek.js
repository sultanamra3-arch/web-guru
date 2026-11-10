// Membuat proyek Android TWA "SiGuru Admin" (Chrome asli, layar penuh untuk semua alamat sekolah).
const { TwaManifest, TwaGenerator, ConsoleLog } = require('@bubblewrap/core');
(async () => {
  const m = new TwaManifest({
    packageId: 'id.my.otomasismpn6keritang.siguruadmin',
    host: 'guru.otomasismpn6keritang.my.id',
    name: 'SiGuru Admin - SMP Negeri 6 Keritang',
    launcherName: 'SiGuru Admin',
    display: 'standalone',
    themeColor: '#2A2C84',
    themeColorDark: '#1B1D5E',
    navigationColor: '#FFFFFF',
    navigationColorDark: '#1B1D5E',
    navigationDividerColor: '#E3E8F2',
    navigationDividerColorDark: '#1B1D5E',
    backgroundColor: '#FFFFFF',
    enableNotifications: false,
    startUrl: '/?p=beranda',
    iconUrl: 'http://127.0.0.1:8090/aset/ikon3-512.png',
    maskableIconUrl: 'http://127.0.0.1:8090/aset/ikon3-512-mask.png',
    splashScreenFadeOutDuration: 300,
    signingKey: { path: '/home/claude/apk/siguru-admin.keystore', alias: 'siguru' },
    appVersionName: '1.0.0',
    appVersionCode: 1,
    shortcuts: [],
    generatorApp: 'bubblewrap-cli',
    webManifestUrl: 'https://guru.otomasismpn6keritang.my.id/manifest.webmanifest',
    fallbackType: 'customtabs',
    features: {},
    alphaDependencies: { enabled: false },
    enableSiteSettingsShortcut: false,
    isChromeOSOnly: false,
    isMetaQuest: false,
    orientation: 'default',
    fingerprints: [],
    additionalTrustedOrigins: [
      'kelola.otomasismpn6keritang.my.id',
      'otomasismpn6keritang.my.id',
      'kartu.otomasismpn6keritang.my.id',
      'bukutamu.otomasismpn6keritang.my.id',
      'katalog.otomasismpn6keritang.my.id',
    ],
    retainedBundles: [],
    minSdkVersion: 21,
  });
  await new TwaGenerator().createTwaProject('/home/claude/apk/proyek', m, new ConsoleLog('twa'));
  require('fs').writeFileSync('/home/claude/apk/proyek/twa-manifest.json', JSON.stringify(m.toJson(), null, 2));
  console.log('selesai');
})().catch(e => { console.error(e); process.exit(1); });
