"""Isolated HTTP integration test, no production data touched."""
import concurrent.futures
import csv
import http.cookiejar
import io
import json
import os
import re
import shutil
import signal
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

ROOT=Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory() as tmp:
    web=Path(tmp)/'web'; shutil.copytree(ROOT/'web',web)
    shutil.rmtree(web/'data',ignore_errors=True)
    with socket.socket() as sock:
        sock.bind(('127.0.0.1',0)); port=sock.getsockname()[1]
    log=open(Path(tmp)/'php.log','w+')
    server=subprocess.Popen(['php','-d','session.save_path='+tmp,'-S',f'127.0.0.1:{port}','-t',str(web)],stdout=log,stderr=log,env={**os.environ,'PHP_CLI_SERVER_WORKERS':'4'},start_new_session=True)
    base=f'http://127.0.0.1:{port}/'
    def client():return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def req(c,path,data=None,j=False):
        headers={}
        if data is not None:
            if j:data=json.dumps(data).encode();headers['Content-Type']='application/json'
            else:data=urllib.parse.urlencode(data).encode()
        try:
            with c.open(urllib.request.Request(base+path,data=data,headers=headers),timeout=10) as res:return res.status,res.read().decode('utf-8-sig')
        except urllib.error.HTTPError as e:return e.code,e.read().decode()
    def csrf(html):return re.search(r'(?:id|name)="csrf" value="([a-f0-9]+)"',html)[1]
    def saved():return json.loads((web/'data/answers.php').read_text().split('\n',1)[1])
    try:
        for _ in range(100):
            try:req(client(),'index.php');break
            except OSError:time.sleep(.05)
        else:raise AssertionError('Server start failed')
        users=[]
        for i in range(4):
            c=client();code,html=req(c,'index.php');assert code==200
            assert html.count('<textarea ')==7
            assert 'Keine Namen oder persönlichen Daten eingeben!' in html
            assert '<header>' not in html
            assert 'width="80" height="44"' in html
            code,raw=req(c,'api.php');state=json.loads(raw);assert code==200 and not state['submitted']
            answers={f'q{k}':f'Fachliche Antwort {i} / Frage {k}\nZweite Zeile äöü' for k in range(1,8)}
            if i==0:answers['q1']='<script>alert(1)</script>';answers['q2']='=1+1'
            users.append((c,{'csrf':csrf(html),'round':state['round'],'answers':answers}))
        c,p=users[0]
        for change,want in [({'csrf':'wrong'},403),({'round':'old'},409),({'answers':{}},422),({'answers':{**p['answers'],'q1':'  '}},422),({'answers':{**p['answers'],'q1':'x'*3001}},422),({'answers':{**p['answers'],'q1':7}},422)]:
            code,raw=req(c,'api.php',{**p,**change},True);assert code==want,(code,raw)
        def send(user):return req(user[0],'api.php',user[1],True)
        with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:results=list(pool.map(send,users))
        assert all(code==200 for code,_ in results),results
        # Repeated simultaneous requests from one browser must be idempotent.
        with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:duplicates=list(pool.map(send,[users[0]]*3))
        assert all(json.loads(raw)['duplicate'] for code,raw in duplicates)
        s=saved();assert s['count']==4
        assert set(s)=={'schema','round','count','answers'}
        assert all(len(a)==4 and all(isinstance(t,str) for t in a) for a in s['answers'].values())
        assert set(s['answers']['q1'])=={u[1]['answers']['q1'] for u in users}
        assert not any(k in s for k in ['responses','session','token','time','ip'])
        assert req(client(),'data/answers.php')[1]==''
        assert '<script>alert(1)</script>' not in req(client(),'index.php')[1]
        admin=client();code,html=req(admin,'admin.php');assert 'class="free-answer"' not in html
        code,out=req(admin,'admin.php',{'csrf':csrf(html),'action':'clear','round':p['round']});assert 'Bitte anmelden' in out
        password=re.search(r"'password' => '([^']+)'",(web/'config.php').read_text())[1]
        code,html=req(admin,'admin.php',{'csrf':csrf(html),'action':'login','password':password})
        assert '<strong>4</strong>' in html
        assert '&lt;script&gt;alert(1)&lt;/script&gt;' in html
        assert '<script>alert(1)</script>' not in html
        assert html.count('class="card free-answer"')==28
        code,out=req(admin,'admin.php',{'csrf':csrf(html),'action':'export'})
        rows=list(csv.reader(io.StringIO(out),delimiter=';'))
        assert len(rows)==29 and rows[0]==['Frage','Antwort']
        assert any(row[1]=="'=1+1" for row in rows[1:])
        code,html=req(admin,'admin.php',{'csrf':csrf(html),'action':'clear','round':p['round']})
        assert '<strong>0</strong>' in html and saved()['count']==0
        code,raw=req(c,'api.php');new=json.loads(raw);assert not new['submitted'] and new['round']!=p['round']
        assert send(users[0])[0]==409
        assert req(c,'api.php',{**p,'round':new['round']},True)[0]==200
        assert saved()['count']==1
        (web/'data/answers.php').write_text('broken')
        assert req(c,'api.php')[0]==503
        assert (web/'data/answers.php').read_text()=='broken'
        print('PASS: 7 free-text answers, no linked response records, participant count, duplicates, validation, CSRF, protected results, HTML escaping, CSV formula safety, clear, new round, corrupt-file protection')
    finally:
        os.killpg(server.pid,signal.SIGTERM);server.wait(timeout=10);log.close()
