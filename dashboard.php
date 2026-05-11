<?php require 'config.php'; check_auth(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outlook Panel - 控制台</title>
    <link rel="icon" type="image/png" href="/static/image/favicon/OutlookPanel_Favicon.svg">
    <style>
        :root {
            --bg: #f5f5f7;
            --card-bg: rgba(255, 255, 255, 0.8);
            --border: rgba(0, 0, 0, 0.05);
            --main: #0071e3;
            --main-hover: #0077ed;
            --text: #1d1d1f;
            --text-secondary: #86868b;
            --danger: #ff3b30;
            --glass: blur(20px) saturate(180%);
        }

        body { 
            margin: 0; 
            background: var(--bg); 
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* 导航栏美化：毛玻璃效果 */
        nav { 
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: var(--glass);
            -webkit-backdrop-filter: var(--glass);
            padding: 0 40px; 
            height: 64px;
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid var(--border); 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
        }

        .nav-logo { font-weight: 700; font-size: 20px; letter-spacing: -0.5px; }
        .nav-logo span { color: var(--main); }

        /* 搜索框：更现代的圆角与交互 */
        .search-bar { 
            background: rgba(0, 0, 0, 0.05);
            border: none; 
            padding: 8px 16px; 
            border-radius: 10px; 
            outline: none; 
            width: 240px; 
            font-size: 14px; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            margin-left: 20px;
        }
        .search-bar:focus { width: 320px; background: #fff; box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.1); }

        .container { padding: 40px; max-width: 1300px; margin: 0 auto; min-height: 80vh; }

        /* 网格布局优化 */
        .grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 24px; 
        }

        /* 卡片美化：悬浮呼吸感 */
        .card { 
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 24px; 
            border-radius: 18px; 
            cursor: pointer; 
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: ""; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
            background: var(--main); opacity: 0; transition: 0.3s;
        }
        .card:hover { 
            transform: translateY(-5px) scale(1.02); 
            box-shadow: 0 20px 40px rgba(0,0,0,0.08); 
            background: #fff;
        }
        .card:hover::before { opacity: 1; }

        .card h3 { margin: 0 0 10px; font-size: 17px; font-weight: 600; color: var(--text); word-break: break-all; }
        .card p { margin: 0; font-size: 14px; color: var(--text-secondary); line-height: 1.5; }

        .card-actions { 
            position: absolute; top: 20px; right: 20px; 
            display: flex; gap: 10px; opacity: 0; transform: translateX(10px); transition: 0.3s;
        }
        .card:hover .card-actions { opacity: 1; transform: translateX(0); }

        .action-icon { 
            width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
            background: #f5f5f7; border-radius: 50%; font-size: 14px; transition: 0.2s;
        }
        .action-icon:hover { background: #e8e8ed; transform: scale(1.1); }

        /* 按钮样式美化 */
        .btn { 
            padding: 8px 18px; border-radius: 10px; cursor: pointer; font-size: 14px; 
            border: 1px solid var(--border); background: #fff; font-weight: 500; 
            transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn:hover { background: #f5f5f7; border-color: #d2d2d7; }
        .btn-black { background: var(--main); color: #FFF; border: none; }
        .btn-black:hover { background: var(--main-hover); transform: translateY(-1px); }
        .btn-danger { color: var(--danger); border-color: rgba(255, 59, 48, 0.1); }
        .btn-danger:hover { background: rgba(255, 59, 48, 0.05); border-color: var(--danger); }

        /* 模态框美化 */
        .modal { 
            display:none; position:fixed; inset:0; 
            background: rgba(0, 0, 0, 0.2); 
            z-index: 2000; align-items: center; justify-content: center; 
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .modal-body { 
            background: #fff; border-radius: 24px; width: 95%; max-width: 1100px; height: 85vh; 
            display: flex; overflow: hidden; position: relative; 
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
        }

        .mail-list { width: 320px; border-right: 1px solid var(--border); overflow-y: auto; background: #fbfbfd; }
        .mail-content { flex: 1; overflow-y: auto; background: #fff; position: relative; }
        
        .mail-item { 
            padding: 16px 20px; border-bottom: 1px solid var(--border); cursor: pointer; 
            transition: 0.2s; border-left: 4px solid transparent;
        }
        .mail-item:hover { background: rgba(0,0,0,0.02); }
        .mail-item.active { background: #fff; border-left-color: var(--main); }

        /* 空状态提示 */
        .empty-state {
            grid-column: 1 / -1; text-align: center; padding: 100px 0; color: var(--text-secondary);
        }
        .empty-state div { font-size: 48px; margin-bottom: 20px; }

        /* 设置表单微调 */
        input, textarea { 
            width: 100%; padding: 12px 14px; border: 1px solid #d2d2d7; 
            border-radius: 10px; margin-top: 6px; margin-bottom: 16px; 
            box-sizing: border-box; font-size: 15px; transition: 0.2s;
        }
        input:focus { border-color: var(--main); outline: none; box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.1); }
        label { font-size: 12px; color: var(--text-secondary); font-weight: 600; margin-left: 2px; }
    </style>
</head>
<body>

<nav>
    <div style="display:flex; align-items:center;">
        <div class="nav-logo">Outlook<span>Panel</span></div>
        <input type="text" id="searchInput" class="search-bar" placeholder="搜索邮箱或备注..." oninput="doSearch()">
    </div>
    <div style="display:flex; gap:12px;">
        <button class="btn btn-black" onclick="openAdd()">+ 添加账号</button>
        <button class="btn" onclick="document.getElementById('importFile').click()">导入</button>
        <input type="file" id="importFile" hidden onchange="importJSON(this)" accept=".json,.txt">
        <button class="btn" onclick="location.href='api.php?action=export'">导出</button>
        <button class="btn" onclick="askConfirm('同步当前所有数据到 TG 机器人？', () => fetch('api.php?action=tg_backup'))">TG备份</button>
        <button class="btn" onclick="openSettings()">⚙️ 设置</button>
        <button class="btn btn-danger" onclick="location.href='api.php?action=logout'">退出</button>
    </div>
</nav>

<div class="container">
    <div id="grid" class="grid">
        <!-- 动态加载内容 -->
    </div>
</div>

<!-- 统一模态框：表单 -->
<div id="formModal" class="modal">
    <div class="modal-body" style="max-width:460px; height:auto; flex-direction:column; padding:32px; overflow-y:auto; max-height:90vh;">
        <h2 id="modalTitle" style="margin:0 0 24px 0; font-size:22px; font-weight:700;"></h2>
        <div id="modalForm"></div>
        <div style="display:flex; gap:12px; margin-top:20px;">
            <button class="btn" onclick="closeModals()" style="flex:1; justify-content:center; padding:12px;">取消</button>
            <button class="btn btn-black" id="submitBtn" style="flex:2; justify-content:center; padding:12px;">确认提交</button>
        </div>
    </div>
</div>

<!-- 确认对话框 -->
<div id="confirmModal" class="modal">
    <div class="confirm-box" style="background:#fff; padding:32px; border-radius:24px; width:340px; text-align:center;">
        <div style="font-size:40px; margin-bottom:16px;">⚠️</div>
        <p id="confirmMsg" style="font-weight:600; font-size:16px; margin:0; line-height:1.5;"></p>
        <div style="display:flex; gap:12px; margin-top:24px;">
            <button class="btn" style="flex:1; justify-content:center;" onclick="closeModals()">取消</button>
            <button class="btn btn-black" style="flex:1; justify-content:center; background:var(--danger);" id="confirmBtn">确定</button>
        </div>
    </div>
</div>

<!-- 邮件查看器 -->
<div id="mailModal" class="modal">
    <div class="modal-body">
        <div style="position:absolute; top:20px; right:20px; z-index:100;">
            <button class="btn" style="border-radius:50%; width:40px; height:40px; padding:0; justify-content:center;" onclick="closeModals()">&times;</button>
        </div>
        <div class="mail-list" id="mailList"></div>
        <div class="mail-content" id="mailContent">
            <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#ccc;">
                请选择左侧邮件查看内容
            </div>
        </div>
    </div>
</div>

<script>
let allAccounts = [];

async function loadList() {
    const res = await fetch('api.php?action=list');
    allAccounts = await res.json();
    renderGrid(allAccounts);
}

function renderGrid(data) {
    const grid = document.getElementById('grid');
    if (data.length === 0) {
        grid.innerHTML = `<div class="empty-state"><div>📥</div><p>还没有账号，点击上方“添加账号”开始</p></div>`;
        return;
    }
    grid.innerHTML = data.map(i => `
        <div class="card" onclick="viewMail(${i.id})">
            <div class="card-actions">
                <span class="action-icon" title="编辑" onclick="openEdit(event, ${i.id}, '${btoa(encodeURIComponent(JSON.stringify(i)))}')">✏️</span>
                <span class="action-icon" style="color:var(--danger)" title="删除" onclick="event.stopPropagation(); askConfirm('确定删除 ${i.email}？', () => delAcc(${i.id}))">🗑️</span>
            </div>
            <h3>${i.email}</h3>
            <p>${i.remark || '<span style="opacity:0.5;font-style:italic;">无备注信息</span>'}</p>
            <div style="margin-top:15px; font-size:11px; color:#bbb; font-family:monospace;">ID: ${i.id}</div>
        </div>
    `).join('');
}

function doSearch() {
    const kw = document.getElementById('searchInput').value.toLowerCase();
    const filtered = allAccounts.filter(acc => 
        acc.email.toLowerCase().includes(kw) || 
        (acc.remark && acc.remark.toLowerCase().includes(kw))
    );
    renderGrid(filtered);
}

function askConfirm(msg, cb) {
    document.getElementById('confirmMsg').innerText = msg;
    document.getElementById('confirmModal').style.display = 'flex';
    document.getElementById('confirmBtn').onclick = () => { cb(); closeModals(); };
}

async function viewMail(id) {
    document.getElementById('mailModal').style.display = 'flex';
    document.getElementById('mailList').innerHTML = '<div style="padding:40px; text-align:center; color:#999;">正在同步邮件...</div>';
    document.getElementById('mailContent').innerHTML = '<div style="display:flex; align-items:center; justify-content:center; height:100%; color:#ccc;">加载中...</div>';
    
    try {
        const res = await fetch('api.php?action=get_mails&id=' + id);
        const data = await res.json();
        
        if (data.error) {
            document.getElementById('mailList').innerHTML = `<div style="padding:20px; color:var(--danger); font-size:14px;">${data.error}</div>`;
            return;
        }

        const messages = data.value || [];
        if(messages.length > 0) {
            document.getElementById('mailList').innerHTML = messages.map(m => {
                const subject = m.subject || '(无主题)';
                const from = m.from?.emailAddress?.name || '未知';
                const body = btoa(unescape(encodeURIComponent(m.body?.content || '')));
                return `
                    <div class="mail-item" onclick="showMsg(this, \`${body}\`)">
                        <div style="font-weight:600; font-size:14px; margin-bottom:4px; color:var(--text);">${subject}</div>
                        <div style="font-size:12px; color:var(--text-secondary);">${from}</div>
                    </div>`;
            }).join('');
        } else {
            document.getElementById('mailList').innerHTML = '<p style="padding:40px; text-align:center; color:#999;">收件箱为空</p>';
        }
    } catch (err) {
        document.getElementById('mailList').innerHTML = `<div style="padding:20px; color:red">网络错误</div>`;
    }
}

function showMsg(el, content) {
    document.querySelectorAll('.mail-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
    let decoded = decodeURIComponent(escape(atob(content)));
    decoded = decoded.replace(/href="\//gi, 'href="#').replace(/src="\//gi, 'src="#').replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
    document.getElementById('mailContent').innerHTML = `<iframe id="mailIframe" style="width:100%; height:100%; border:none"></iframe>`;
    const doc = document.getElementById('mailIframe').contentWindow.document;
    doc.open(); doc.write(decoded); doc.close();
}

function openAdd() {
    showForm('添加新账号', `
        <label>原始数据</label>
        <textarea id="raw_data" rows="4" placeholder="Email----Pass----ID----RefreshToken"></textarea>
        <label>备注</label>
        <input id="remark" placeholder="例如：自用主号">
    `, async () => {
        let fd = new FormData();
        fd.append('raw_data', document.getElementById('raw_data').value);
        fd.append('remark', document.getElementById('remark').value);
        await fetch('api.php?action=add', {method:'POST', body:fd});
    });
}

function openEdit(e, id, encoded) {
    e.stopPropagation();
    const d = JSON.parse(decodeURIComponent(atob(encoded)));
    showForm('编辑信息', `
        <label>邮箱</label><input id="e_mail" value="${d.email}">
        <label>密码</label><input id="e_pass" value="${d.password}">
        <label>Client ID</label><input id="e_cid" value="${d.client_id}">
        <label>Refresh Token</label><textarea id="e_token" rows="3">${d.refresh_token}</textarea>
        <label>备注</label><input id="e_rem" value="${d.remark||''}">
    `, async () => {
        let fd = new FormData();
        fd.set('id', id); fd.set('email', document.getElementById('e_mail').value);
        fd.set('password', document.getElementById('e_pass').value); 
        fd.set('client_id', document.getElementById('e_cid').value);
        fd.set('refresh_token', document.getElementById('e_token').value); 
        fd.set('remark', document.getElementById('e_rem').value);
        await fetch('api.php?action=update_account', {method:'POST', body:fd});
    });
}

async function openSettings() {
    const data = await (await fetch('api.php?action=get_settings')).json();
    showForm('系统设置', `
        <label>管理员用户名</label><input id="s_user" value="${data.admin_user}">
        <label>设置新密码 (不改留空)</label><input type="password" id="s_pass">
        <hr style="border:0; border-top:1px solid #eee; margin:10px 0;">
        <label>TG Bot Token</label><input id="s_token" value="${data.tg_token}">
        <label>TG Chat ID</label><input id="s_chat" value="${data.tg_chatid}">
    `, async () => {
        let fd = new FormData();
        fd.set('admin_user', document.getElementById('s_user').value); 
        fd.set('admin_pass', document.getElementById('s_pass').value);
        fd.set('tg_token', document.getElementById('s_token').value); 
        fd.set('tg_chatid', document.getElementById('s_chat').value);
        await fetch('api.php?action=save_settings', {method:'POST', body:fd});
    });
}

function showForm(title, html, cb) {
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalForm').innerHTML = html;
    document.getElementById('formModal').style.display = 'flex';
    document.getElementById('submitBtn').onclick = async () => { 
        document.getElementById('submitBtn').innerText = '提交中...';
        await cb(); 
        document.getElementById('submitBtn').innerText = '确认提交';
        closeModals(); loadList(); 
    };
}

async function importJSON(input) {
    if (!input.files.length) return;
    let fd = new FormData(); fd.append('file', input.files[0]);
    await fetch('api.php?action=import', {method:'POST', body:fd});
    loadList(); input.value = '';
}

async function delAcc(id) {
    let fd = new FormData(); fd.append('id', id);
    await fetch('api.php?action=delete', {method:'POST', body:fd});
    loadList();
}

function closeModals() { document.querySelectorAll('.modal').forEach(m => m.style.display='none'); }
loadList();
</script>
</body>
</html>
