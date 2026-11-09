import re, os, sys, shutil, html, urllib.parse as up, requests
U='http://127.0.0.1:8092/'; OUT=sys.argv[1]; APP=sys.argv[2]
shutil.rmtree(OUT, ignore_errors=True); os.makedirs(OUT)
s=requests.Session()
r=s.get(U+'?p=masuk'); t=re.search(r'name="_t" value="([^"]+)"', r.text).group(1)
r=s.post(U+'?p=masuk', data={'_t':t,'username':'admin','password':'admin123'}); assert 'Halo' in r.text, r.text[:300]
def nama(q):
    u=up.parse_qsl(q, keep_blank_values=True); d=[(k,v) for k,v in u if k not in ('p','_t') and v!='']
    p=dict(u).get('p','beranda'); d.sort()
    n=p+''.join('__%s-%s'%(k,v) for k,v in d)
    return re.sub(r'[^A-Za-z0-9_-]+','-',n)
LEWAT={'keluar','api_ai','masuk'}
antri=['p=beranda','p=absensi&kelas=VIII A&mapel=Informatika&tanggal=2026-11-04','p=absensi_rekap&kelas=VIII A&bulan=2026-10','p=nilai_rekap&kelas=VII A']
seen=set(); ext_of={}
while antri:
    q=antri.pop(0); q=html.unescape(q)
    n=nama(q)
    if n in seen: continue
    p=dict(up.parse_qsl(q)).get('p','')
    if p in LEWAT: continue
    seen.add(n)
    r=s.get(U+'?'+q)
    ct=r.headers.get('Content-Type','')
    if 'text/html' not in ct:
        ext='.doc' if 'msword' in ct else '.csv' if 'csv' in ct else '.bin'
        open(os.path.join(OUT,n+ext),'wb').write(r.content); ext_of[n]=ext; continue
    ext_of[n]='.html'
    for m in re.findall(r'href="\?([^"]*)"', r.text): antri.append(m)
    open(os.path.join(OUT,n+'.html'),'w').write(r.text)
# data pantau ujian (agar halaman pantau tampil di pratinjau)
for f in list(ext_of):
    m=re.match(r'ujian_lihat__id-(\d+)$', f)
    if m:
        r=s.get(U+'?p=api_ujian&aksi=pantau&id='+m.group(1))
        if r.ok: open(os.path.join(OUT,'pantau-'+m.group(1)+'.json'),'w').write(r.text)
# tulis ulang tautan
for f in os.listdir(OUT):
    if not f.endswith('.html'): continue
    path=os.path.join(OUT,f); t=open(path).read()
    def ganti(m):
        q=html.unescape(m.group(1)); n=nama(q); p=dict(up.parse_qsl(q)).get('p','')
        if p=='keluar' or p=='masuk': return 'href="index.html"'
        return 'href="%s%s"'%(n, ext_of.get(n,'.html')) if n in ext_of else 'href="#" data-demo="1"'
    t=re.sub(r'href="\?([^"]*)"', ganti, t)
    t=re.sub(r'action="\?p=[^"]*"', '', t)
    t=re.sub(r'<code>/[^<]*ai-kunci\.php</code>','<code>data/ai-kunci.php</code>',t)
    t=t.replace('<head>','<head>\n<script src="aset/pratinjau.js"></script>',1)
    t=t.replace('<link rel="manifest" href="manifest.webmanifest','<link rel="x-manifest" href="manifest.webmanifest')
    open(path,'w').write(t)
shutil.copy(os.path.join(OUT,'beranda.html'), os.path.join(OUT,'index.html'))
shutil.copytree(os.path.join(APP,'aset'), os.path.join(OUT,'aset'))
shutil.copy(os.path.join(os.path.dirname(__file__),'pratinjau.js'), os.path.join(OUT,'aset','pratinjau.js'))
open(os.path.join(OUT,'.nojekyll'),'w').write('')
print(len(ext_of),'berkas', sorted(set(e for e in ext_of.values())))
