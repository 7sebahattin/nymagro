<style>
/* YENI EKLENEN HEAD STYLLERI */
/* ════════════════════════════════════
       ACTION BUTTONS (screenshot'taki butonlar)
    ════════════════════════════════════ */
    .action-btns { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .btn-action {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 16px; border: none; border-radius: 5px;
      font-size: 13px; font-weight: 600; cursor: pointer;
      transition: filter .15s, box-shadow .15s; text-decoration: none; white-space: nowrap;
    }
    .btn-action:hover { filter: brightness(1.12); box-shadow: 0 3px 10px rgba(0,0,0,.18); }
    .btn-perakende { background: #27ae60; color: #fff; }
    .btn-yeni-must { background: #16a085; color: #fff; }
    .btn-kayitli   { background: #c0392b; color: #fff; }
    .btn-serbest   { background: #e67e22; color: #fff; }

    /* ════════════════════════════════════
       FILTER PANEL (beyaz kart)
    ════════════════════════════════════ */
    .filter-panel {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 8px 8px 0 0;
      border-bottom: none;
    }
    .filter-row {
      display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
      padding: 10px 14px;
    }
    .filter-row + .filter-row {
      border-top: 1px solid var(--border);
    }

    /* Belge Tipi custom dropdown */
    .belge-tipi-wrap { position: relative; }
    .belge-tipi-btn {
      display: flex; align-items: center; justify-content: space-between; gap: 8px;
      padding: 6px 12px; border: 1px solid var(--border2); border-radius: 6px;
      background: var(--card-bg); font-size: 13px; color: var(--text2); cursor: pointer;
      min-width: 170px; transition: border-color .15s;
    }
    .belge-tipi-btn:hover, .belge-tipi-btn.open { border-color: var(--muted); }
    .belge-tipi-btn i { font-size: 10px; color: var(--muted); transition: transform .2s; }
    .belge-tipi-btn.open i { transform: rotate(180deg); }
    .belge-dropdown {
      position: absolute; top: calc(100% + 3px); left: 0; z-index: 300;
      background: var(--card-bg); border: 1px solid var(--border2); border-radius: 6px;
      box-shadow: 0 6px 20px rgba(0,0,0,.1); min-width: 210px;
      display: none; padding: 5px 0;
    }
    .belge-dropdown.open { display: block; }
    .belge-chk-item {
      display: flex; align-items: center; gap: 8px;
      padding: 7px 14px; font-size: 13px; color: var(--text2); cursor: pointer;
    }
    .belge-chk-item:hover { background: var(--surface-2); }
    .belge-chk-item.indent { padding-left: 30px; }
    .belge-chk-item input[type="checkbox"] { width: 14px; height: 14px; accent-color: var(--text); cursor: pointer; flex-shrink: 0; }

    /* Son 1 Ay dropdown */
    .period-wrap { position: relative; }
    .btn-period {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; background: #334155; color: var(--text);
      border: none; border-radius: 6px; font-size: 13px; font-weight: 500;
      cursor: pointer; transition: background .15s;
    }
    .btn-period:hover { background: #1e293b; }
    .btn-period i { font-size: 9px; }
    .period-dropdown {
      position: absolute; top: calc(100% + 3px); left: 0; z-index: 300;
      background: var(--card-bg); border: 1px solid var(--border2); border-radius: 6px;
      box-shadow: 0 6px 20px rgba(0,0,0,.1); min-width: 160px;
      display: none; padding: 4px 0;
    }
    .period-dropdown.open { display: block; }
    .period-item { padding: 7px 16px; font-size: 13px; color: var(--text2); cursor: pointer; }
    .period-item:hover { background: var(--surface-2); }
    .period-item.active { font-weight: 700; color: var(--text); background: var(--surface-2); }

    /* İptalleri toggle */
    .iptalli-wrap { display: flex; align-items: center; gap: 7px; margin-left: auto; font-size: 13px; color: var(--text2); font-weight: 500; white-space: nowrap; }
    .toggle-switch { position: relative; width: 38px; height: 21px; flex-shrink: 0; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; inset: 0; background: #cbd5e1; border-radius: 21px; cursor: pointer; transition: background .2s; }
    .toggle-slider::before { content: ''; position: absolute; width: 15px; height: 15px; left: 3px; top: 3px; background: var(--card-bg); border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
    .toggle-switch input:checked + .toggle-slider { background: #22c55e; }
    .toggle-switch input:checked + .toggle-slider::before { transform: translateX(17px); }

    /* Arama satırı */
    .search-label { font-size: 13px; color: var(--text2); font-weight: 600; white-space: nowrap; margin-left: auto; }
    .search-type-select {
      padding: 6px 10px; border: 1px solid var(--border2); border-radius: 6px;
      font-size: 13px; color: var(--text2); background: var(--card-bg); cursor: pointer; outline: none;
    }
    .search-type-select:focus { border-color: #4ade80; }
    .search-input-wrap { position: relative; }
    .search-input-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 12px; pointer-events: none; }
    .search-txt {
      padding: 6px 12px 6px 30px; border: 1px solid var(--border2); border-radius: 6px;
      font-size: 13px; color: var(--text2); background: var(--card-bg); outline: none;
      width: 260px; transition: border-color .2s, box-shadow .2s;
    }
    .search-txt:focus { border-color: #4ade80; box-shadow: 0 0 0 3px rgba(74,222,128,.12); }
    .search-txt::placeholder { color: var(--muted); font-style: italic; }

    /* ════════════════════════════════════
       TABLE
    ════════════════════════════════════ */
    .table-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 0 0 8px 8px;
      overflow: hidden;
    }
    .sales-table { width: 100%; border-collapse: collapse; }
    .sales-table thead tr { background: #1e293b; }
    .sales-table thead th {
      padding: 11px 14px; font-size: 12.5px; font-weight: 600;
      color: var(--muted); white-space: nowrap;
      border-right: 1px solid rgba(255,255,255,.06);
    }
    .sales-table thead th:last-child { border-right: none; }
    .th-sort { cursor: pointer; display: inline-flex; align-items: center; gap: 4px; user-select: none; }
    .th-sort:hover { color: var(--text); }
    .sort-ic { font-size: 10px; color: var(--text2); }

    /* Data rows */
    .sales-table tbody tr.data-row { border-bottom: 1px solid var(--border); transition: background .12s; }
    .sales-table tbody tr.data-row:hover { background: var(--surface-2); }
    .sales-table tbody tr.data-row.expanded { background: rgba(59,130,246,.15); }
    .sales-table tbody tr.data-row:last-child { border-bottom: none; }
    .sales-table tbody td { padding: 9px 14px; font-size: 13px; color: var(--text2); vertical-align: middle; }

    /* Toggle button */
    .td-toggle { width: 40px; text-align: center; }
    .row-toggle {
      width: 22px; height: 22px; border-radius: 50%;
      border: 2px solid; display: inline-flex; align-items: center; justify-content: center;
      font-size: 15px; font-weight: 700; cursor: pointer; transition: background .12s; line-height: 1;
      background: transparent;
    }
    .row-toggle.plus  { border-color: #22c55e; color: #22c55e; }
    .row-toggle.minus { border-color: #ef4444; color: #ef4444; }
    .row-toggle.plus:hover  { background: rgba(34,197,94,.08); }
    .row-toggle.minus:hover { background: rgba(239,68,68,.08); }

    /* Customer link */
    .cust-link { color: #2563eb; text-decoration: none; font-weight: 500; font-size: 13px; }
    .cust-link:hover { text-decoration: underline; }

    /* Amount */
    .td-amount { text-align: right; font-weight: 600; font-size: 13px; color: var(--text); }

    /* Status badges */
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .status-faturalasms { background: #22c55e; color: #fff; }
    .status-bekliyor    { background: #f59e0b; color: #fff; }
    .status-siparis     { background: #3b82f6; color: #fff; }
    .status-iptal       { background: #94a3b8; color: #fff; }

    /* Detail row */
    .detail-row { display: none; }
    .detail-row.open { display: table-row; }
    .detail-row > td { padding: 0; border-bottom: 2px solid var(--border); background: var(--surface-2); }
    .detail-inner { padding: 10px 14px 14px 54px; }

    .detail-btns { display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; }
    .btn-det {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 5px 13px; border: none; border-radius: 5px;
      font-size: 12.5px; font-weight: 600; cursor: pointer; transition: filter .15s;
    }
    .btn-det:hover { filter: brightness(1.12); }
    .btn-det.red   { background: #ef4444; color: #fff; }
    .btn-det.green { background: #22c55e; color: #fff; }
    .btn-det.blue  { background: #3b82f6; color: #fff; }

    .detail-prod-table { border-collapse: collapse; min-width: 500px; max-width: 680px; }
    .detail-prod-table thead th { padding: 5px 10px; font-size: 12px; font-weight: 700; color: var(--muted); border-bottom: 2px solid var(--border); text-align: left; }
    .detail-prod-table tbody td { padding: 6px 10px; font-size: 13px; color: var(--text2); border-bottom: 1px solid var(--border); }
    .detail-prod-table tbody tr:last-child td { border-bottom: none; }
    .prod-thumb { width: 34px; height: 34px; background: var(--surface-2); border-radius: 5px; display: flex; align-items: center; justify-content: center; color: var(--muted); font-size: 14px; }
    .td-num { text-align: right; }
    .detail-user-line { margin-top: 10px; font-size: 12px; color: var(--muted); }
    .detail-user-line strong { color: var(--text2); }

    /* Empty state */
    .empty-state { text-align: center; padding: 48px 16px; color: var(--muted); font-size: 14px; }
    .empty-state i { font-size: 32px; display: block; margin-bottom: 10px; }

    /* Pagination */
    .pag-bar { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-top: 1px solid var(--border); background: var(--surface-2); }
    .pag-info { font-size: 12.5px; color: var(--muted); }
    .pag-btns { display: flex; gap: 3px; }
    .pag-btn { width: 30px; height: 30px; border: 1px solid var(--border); border-radius: 6px; background: var(--card-bg); font-size: 12px; color: var(--text2); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .12s, border-color .12s; }
    .pag-btn:hover { background: var(--surface-2); }
    .pag-btn.active { background: #1e293b; border-color: var(--text); color: #4ade80; font-weight: 700; }
    .pag-btn:disabled { opacity: .35; cursor: default; }

    /* Toast */
    .toast-container { position: fixed; bottom: 22px; right: 22px; z-index: 9999; display: flex; flex-direction: column; gap: 7px; }
    .toast-msg { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; box-shadow: 0 3px 14px rgba(0,0,0,.13); }
    .toast-msg.success { background: var(--card-bg); border-left: 4px solid #22c55e; }
    .toast-msg.error   { background: var(--card-bg); border-left: 4px solid #ef4444; }
    .toast-msg.info    { background: var(--card-bg); border-left: 4px solid #3b82f6; }

    
    

    /* ══ YENİ MÜŞTERİ MODAL ══ */
    .ym-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,.55);
      z-index: 800; display: none; align-items: center; justify-content: center;
    }
    .ym-overlay.open { display: flex; }
    .ym-modal {
      background: var(--card-bg); border-radius: 6px; width: 520px; max-width: 96vw;
      box-shadow: 0 8px 40px rgba(0,0,0,.25); overflow: hidden;
      display: flex; flex-direction: column; max-height: 94vh;
    }
    .ym-header {
      background: #6ad0a5; color: #fff; padding: 14px 18px;
      font-size: 15px; font-weight: 700;
      display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
    }
    .ym-close { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; line-height: 1; opacity: .85; }
    .ym-close:hover { opacity: 1; }
    .ym-body { padding: 18px 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
    .ym-info { background: rgba(243,156,18,.13); border: 1px solid rgba(243,156,18,.30); border-radius: 5px; padding: 10px 14px; font-size: 13px; color: var(--warning); line-height: 1.55; }
    .ym-info strong { color: var(--warning); }
    .ym-group { display: flex; flex-direction: column; gap: 4px; }
    .ym-group label { font-size: 12.5px; font-weight: 600; color: var(--text2); }
    .ym-input, .ym-select, .ym-textarea {
      padding: 7px 10px; border: 1px solid var(--border2); border-radius: 5px;
      font-size: 13px; color: var(--text); outline: none; background: var(--card-bg);
      transition: border-color .2s; width: 100%;
    }
    .ym-input:focus, .ym-select:focus, .ym-textarea:focus { border-color: #2ecc71; box-shadow: 0 0 0 3px rgba(46,204,113,.12); }
    .ym-textarea { resize: vertical; min-height: 70px; }
    .ym-select { cursor: pointer; }
    .ym-hint { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
    .ym-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .ym-footer { padding: 12px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 8px; flex-shrink: 0; }
    .ym-btn-vazgec { display: inline-flex; align-items: center; gap: 5px; padding: 8px 18px; background: #e67e22; color: #fff; border: none; border-radius: 5px; font-size: 13px; font-weight: 600; cursor: pointer; transition: filter .15s; }
    .ym-btn-vazgec:hover { filter: brightness(1.1); }
    .ym-btn-devam  { display: inline-flex; align-items: center; gap: 5px; padding: 8px 18px; background: #27ae60; color: #fff; border: none; border-radius: 5px; font-size: 13px; font-weight: 600; cursor: pointer; transition: filter .15s; }
    .ym-btn-devam:hover { filter: brightness(1.1); }
    
    
  
  
  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<style>
/* ════════════════════════════════════
       ACTION BUTTONS (screenshot'taki butonlar)
    ════════════════════════════════════ */
    .action-btns { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .btn-action {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 16px; border: none; border-radius: 5px;
      font-size: 13px; font-weight: 600; cursor: pointer;
      transition: filter .15s, box-shadow .15s; text-decoration: none; white-space: nowrap;

    .btn-action:hover { filter: brightness(1.12); box-shadow: 0 3px 10px rgba(0,0,0,.18); }
    .btn-perakende { background: #27ae60; color: #fff; }
    .btn-yeni-must { background: #16a085; color: #fff; }
    .btn-kayitli   { background: #c0392b; color: #fff; }
    .btn-serbest   { background: #e67e22; color: #fff; }

    /* ════════════════════════════════════
       FILTER PANEL (beyaz kart)
    ════════════════════════════════════ */
    .filter-panel {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 8px 8px 0 0;
      border-bottom: none;

    .filter-row {
      display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
      padding: 10px 14px;

    .filter-row + .filter-row {
      border-top: 1px solid var(--border);

    /* Belge Tipi custom dropdown */
    .belge-tipi-wrap { position: relative; }
    .belge-tipi-btn {
      display: flex; align-items: center; justify-content: space-between; gap: 8px;
      padding: 6px 12px; border: 1px solid var(--border2); border-radius: 6px;
      background: var(--card-bg); font-size: 13px; color: var(--text2); cursor: pointer;
      min-width: 170px; transition: border-color .15s;

    .belge-tipi-btn:hover, .belge-tipi-btn.open { border-color: var(--muted); }
    .belge-tipi-btn i { font-size: 10px; color: var(--muted); transition: transform .2s; }
    .belge-tipi-btn.open i { transform: rotate(180deg); }
    .belge-dropdown {
      position: absolute; top: calc(100% + 3px); left: 0; z-index: 300;
      background: var(--card-bg); border: 1px solid var(--border2); border-radius: 6px;
      box-shadow: 0 6px 20px rgba(0,0,0,.1); min-width: 210px;
      display: none; padding: 5px 0;

    .belge-dropdown.open { display: block; }
    .belge-chk-item {
      display: flex; align-items: center; gap: 8px;
      padding: 7px 14px; font-size: 13px; color: var(--text2); cursor: pointer;

    .belge-chk-item:hover { background: var(--surface-2); }
    .belge-chk-item.indent { padding-left: 30px; }
    .belge-chk-item input[type="checkbox"] { width: 14px; height: 14px; accent-color: var(--text); cursor: pointer; flex-shrink: 0; }

    /* Son 1 Ay dropdown */
    .period-wrap { position: relative; }
    .btn-period {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; background: #334155; color: var(--text);
      border: none; border-radius: 6px; font-size: 13px; font-weight: 500;
      cursor: pointer; transition: background .15s;

    .btn-period:hover { background: #1e293b; }
    .btn-period i { font-size: 9px; }
    .period-dropdown {
      position: absolute; top: calc(100% + 3px); left: 0; z-index: 300;
      background: var(--card-bg); border: 1px solid var(--border2); border-radius: 6px;
      box-shadow: 0 6px 20px rgba(0,0,0,.1); min-width: 160px;
      display: none; padding: 4px 0;

    .period-dropdown.open { display: block; }
    .period-item { padding: 7px 16px; font-size: 13px; color: var(--text2); cursor: pointer; }
    .period-item:hover { background: var(--surface-2); }
    .period-item.active { font-weight: 700; color: var(--text); background: var(--surface-2); }

    /* İptalleri toggle */
    .iptalli-wrap { display: flex; align-items: center; gap: 7px; margin-left: auto; font-size: 13px; color: var(--text2); font-weight: 500; white-space: nowrap; }
    .toggle-switch { position: relative; width: 38px; height: 21px; flex-shrink: 0; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; inset: 0; background: #cbd5e1; border-radius: 21px; cursor: pointer; transition: background .2s; }
    .toggle-slider::before { content: ''; position: absolute; width: 15px; height: 15px; left: 3px; top: 3px; background: var(--card-bg); border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
    .toggle-switch input:checked + .toggle-slider { background: #22c55e; }
    .toggle-switch input:checked + .toggle-slider::before { transform: translateX(17px); }

    /* Arama satırı */
    .search-label { font-size: 13px; color: var(--text2); font-weight: 600; white-space: nowrap; margin-left: auto; }
    .search-type-select {
      padding: 6px 10px; border: 1px solid var(--border2); border-radius: 6px;
      font-size: 13px; color: var(--text2); background: var(--card-bg); cursor: pointer; outline: none;

    .search-type-select:focus { border-color: #4ade80; }
    .search-input-wrap { position: relative; }
    .search-input-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 12px; pointer-events: none; }
    .search-txt {
      padding: 6px 12px 6px 30px; border: 1px solid var(--border2); border-radius: 6px;
      font-size: 13px; color: var(--text2); background: var(--card-bg); outline: none;
      width: 260px; transition: border-color .2s, box-shadow .2s;

    .search-txt:focus { border-color: #4ade80; box-shadow: 0 0 0 3px rgba(74,222,128,.12); }
    .search-txt::placeholder { color: var(--muted); font-style: italic; }

    /* ════════════════════════════════════
       TABLE
    ════════════════════════════════════ */
    .table-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 0 0 8px 8px;
      overflow: hidden;

    .sales-table { width: 100%; border-collapse: collapse; }
    .sales-table thead tr { background: #1e293b; }
    .sales-table thead th {
      padding: 11px 14px; font-size: 12.5px; font-weight: 600;
      color: var(--muted); white-space: nowrap;
      border-right: 1px solid rgba(255,255,255,.06);

    .sales-table thead th:last-child { border-right: none; }
    .th-sort { cursor: pointer; display: inline-flex; align-items: center; gap: 4px; user-select: none; }
    .th-sort:hover { color: var(--text); }
    .sort-ic { font-size: 10px; color: var(--text2); }

    /* Data rows */
    .sales-table tbody tr.data-row { border-bottom: 1px solid var(--border); transition: background .12s; }
    .sales-table tbody tr.data-row:hover { background: var(--surface-2); }
    .sales-table tbody tr.data-row.expanded { background: rgba(59,130,246,.15); }
    .sales-table tbody tr.data-row:last-child { border-bottom: none; }
    .sales-table tbody td { padding: 9px 14px; font-size: 13px; color: var(--text2); vertical-align: middle; }

    /* Toggle button */
    .td-toggle { width: 40px; text-align: center; }
    .row-toggle {
      width: 22px; height: 22px; border-radius: 50%;
      border: 2px solid; display: inline-flex; align-items: center; justify-content: center;
      font-size: 15px; font-weight: 700; cursor: pointer; transition: background .12s; line-height: 1;
      background: transparent;

    .row-toggle.plus  { border-color: #22c55e; color: #22c55e; }
    .row-toggle.minus { border-color: #ef4444; color: #ef4444; }
    .row-toggle.plus:hover  { background: rgba(34,197,94,.08); }
    .row-toggle.minus:hover { background: rgba(239,68,68,.08); }

    /* Customer link */
    .cust-link { color: #2563eb; text-decoration: none; font-weight: 500; font-size: 13px; }
    .cust-link:hover { text-decoration: underline; }

    /* Amount */
    .td-amount { text-align: right; font-weight: 600; font-size: 13px; color: var(--text); }

    /* Status badges */
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .status-faturalasms { background: #22c55e; color: #fff; }
    .status-bekliyor    { background: #f59e0b; color: #fff; }
    .status-siparis     { background: #3b82f6; color: #fff; }
    .status-iptal       { background: #94a3b8; color: #fff; }

    /* Detail row */
    .detail-row { display: none; }
    .detail-row.open { display: table-row; }
    .detail-row > td { padding: 0; border-bottom: 2px solid var(--border); background: var(--surface-2); }
    .detail-inner { padding: 10px 14px 14px 54px; }

    .detail-btns { display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; }
    .btn-det {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 5px 13px; border: none; border-radius: 5px;
      font-size: 12.5px; font-weight: 600; cursor: pointer; transition: filter .15s;

    .btn-det:hover { filter: brightness(1.12); }
    .btn-det.red   { background: #ef4444; color: #fff; }
    .btn-det.green { background: #22c55e; color: #fff; }
    .btn-det.blue  { background: #3b82f6; color: #fff; }

    .detail-prod-table { border-collapse: collapse; min-width: 500px; max-width: 680px; }
    .detail-prod-table thead th { padding: 5px 10px; font-size: 12px; font-weight: 700; color: var(--muted); border-bottom: 2px solid var(--border); text-align: left; }
    .detail-prod-table tbody td { padding: 6px 10px; font-size: 13px; color: var(--text2); border-bottom: 1px solid var(--border); }
    .detail-prod-table tbody tr:last-child td { border-bottom: none; }
    .prod-thumb { width: 34px; height: 34px; background: var(--surface-2); border-radius: 5px; display: flex; align-items: center; justify-content: center; color: var(--muted); font-size: 14px; }
    .td-num { text-align: right; }
    .detail-user-line { margin-top: 10px; font-size: 12px; color: var(--muted); }
    .detail-user-line strong { color: var(--text2); }

    /* Empty state */
    .empty-state { text-align: center; padding: 48px 16px; color: var(--muted); font-size: 14px; }
    .empty-state i { font-size: 32px; display: block; margin-bottom: 10px; }

    /* Pagination */
    .pag-bar { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-top: 1px solid var(--border); background: var(--surface-2); }
    .pag-info { font-size: 12.5px; color: var(--muted); }
    .pag-btns { display: flex; gap: 3px; }
    .pag-btn { width: 30px; height: 30px; border: 1px solid var(--border); border-radius: 6px; background: var(--card-bg); font-size: 12px; color: var(--text2); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .12s, border-color .12s; }
    .pag-btn:hover { background: var(--surface-2); }
    .pag-btn.active { background: #1e293b; border-color: var(--text); color: #4ade80; font-weight: 700; }
    .pag-btn:disabled { opacity: .35; cursor: default; }

    /* Toast */
    .toast-container { position: fixed; bottom: 22px; right: 22px; z-index: 9999; display: flex; flex-direction: column; gap: 7px; }
    .toast-msg { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; box-shadow: 0 3px 14px rgba(0,0,0,.13); }
    .toast-msg.success { background: var(--card-bg); border-left: 4px solid #22c55e; }
    .toast-msg.error   { background: var(--card-bg); border-left: 4px solid #ef4444; }
    .toast-msg.info    { background: var(--card-bg); border-left: 4px solid #3b82f6; }

      .search-txt { width: 180px; }

    /* ══ YENİ MÜŞTERİ MODAL ══ */
    .ym-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,.55);
      z-index: 800; display: none; align-items: center; justify-content: center;

    .ym-overlay.open { display: flex; }
    .ym-modal {
      background: var(--card-bg); border-radius: 6px; width: 520px; max-width: 96vw;
      box-shadow: 0 8px 40px rgba(0,0,0,.25); overflow: hidden;
      display: flex; flex-direction: column; max-height: 94vh;

    .ym-header {
      background: #6ad0a5; color: #fff; padding: 14px 18px;
      font-size: 15px; font-weight: 700;
      display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;

    .ym-close { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; line-height: 1; opacity: .85; }
    .ym-close:hover { opacity: 1; }
    .ym-body { padding: 18px 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
    .ym-info { background: rgba(243,156,18,.13); border: 1px solid rgba(243,156,18,.30); border-radius: 5px; padding: 10px 14px; font-size: 13px; color: var(--warning); line-height: 1.55; }
    .ym-info strong { color: var(--warning); }
    .ym-group { display: flex; flex-direction: column; gap: 4px; }
    .ym-group label { font-size: 12.5px; font-weight: 600; color: var(--text2); }
    .ym-input, .ym-select, .ym-textarea {
      padding: 7px 10px; border: 1px solid var(--border2); border-radius: 5px;
      font-size: 13px; color: var(--text); outline: none; background: var(--card-bg);
      transition: border-color .2s; width: 100%;

    .ym-input:focus, .ym-select:focus, .ym-textarea:focus { border-color: #2ecc71; box-shadow: 0 0 0 3px rgba(46,204,113,.12); }
    .ym-textarea { resize: vertical; min-height: 70px; }
    .ym-select { cursor: pointer; }
    .ym-hint { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
    .ym-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .ym-footer { padding: 12px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 8px; flex-shrink: 0; }
    .ym-btn-vazgec { display: inline-flex; align-items: center; gap: 5px; padding: 8px 18px; background: #e67e22; color: #fff; border: none; border-radius: 5px; font-size: 13px; font-weight: 600; cursor: pointer; transition: filter .15s; }
    .ym-btn-vazgec:hover { filter: brightness(1.1); }
    .ym-btn-devam  { display: inline-flex; align-items: center; gap: 5px; padding: 8px 18px; background: #27ae60; color: #fff; border: none; border-radius: 5px; font-size: 13px; font-weight: 600; cursor: pointer; transition: filter .15s; }
    .ym-btn-devam:hover { filter: brightness(1.1); }
    @media (max-width: 560px) { .ym-row2 { grid-template-columns: 1fr; } }
    
  
  
  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<!-- ── Action Buttons (screenshot'taki 4 buton) ── -->
    <div class="action-btns" style="display:flex; gap:6px; margin-bottom: 16px;">
      <button class="btn-action" style="background:#d9534f; color:#fff; border:none; padding:7px 12px; border-radius:3px; font-weight:600; font-size:12.5px; display:inline-flex; align-items:center; gap:6px;">
        <i class="fa-solid fa-plus"></i> Kayıtlı Müşteriye Teklif Hazırla
      </button>
      <button class="btn-action" style="background:#5bc0de; color:#fff; border:none; padding:7px 12px; border-radius:3px; font-weight:600; font-size:12.5px; display:inline-flex; align-items:center; gap:6px;">
        <i class="fa-solid fa-plus"></i> Yeni Müşteriye Teklif Hazırla
      </button>
    </div>

    <!-- Info Box -->
    <div style="background-color: rgba(243,156,18,.15); border: 1px solid rgba(243,156,18,.28); color: var(--warning); padding: 15px 20px; border-radius: 4px; font-size: 13px; line-height: 1.6; margin-top: 10px;">
      Hiç teklif işlemi kaydetmemişsiniz. Yukarıdaki <span style="color:#5cb85c; font-weight:600;">Yeni Teklif Hazırla</span> düğmesine tıklayarak müşterilerinize teklif hazırlayabilir ve hazırladığınız teklifleri dilerseniz yazdırabilir, dilerseniz online olarak paylaşabilirsiniz.<br>
      Teklif şablonlarınızı tasarlamak için (firma bilgileriniz, logonuz vs) <span style="color:#5cb85c; font-weight:600;">Özel Şablonlar</span> sayfasını kullanabilirsiniz.
    </div>

<script>
  /* Sidebar accordion */
  document.querySelectorAll('.submenu').forEach(sub => {
    const link = document.querySelector(`[data-bs-target="#${sub.id}"]`);
    if (!link) return;
    sub.addEventListener('show.bs.collapse', () => {
      link.setAttribute('aria-expanded', 'true');
      document.querySelectorAll('.submenu.show').forEach(o => {
        if (o.id !== sub.id) bootstrap.Collapse.getInstance(o)?.hide();
      });
    });
    sub.addEventListener('hide.bs.collapse', () => link.setAttribute('aria-expanded', 'false'));
  });
</script>

<script>
(function () {
  'use strict';

  /* ════════ DEMO VERİ ════════ */
  const salesData = [
    { id:1, tarih:'16.04.2026', musteri:'FİPA FİDANCILIK EKOLOJİK TARIM LTD.ŞTİ.',  belgeNo:'NYM2026000000002', siparisNo:'', tutar:28650,  status:'faturalasms', iptal:false,
      urunler:[{ ad:'30 x TİC-CNX CONEX 1 LT', birimFiyat:'795,833333', tutar:'28.650,00' }], kullanici:'FİPA FİDANCILIK EKOLOJİK TARIM LTD.ŞTİ.' },
    { id:2, tarih:'16.04.2026', musteri:'DOĞTAR GIDA TARIM HAYV.İNŞ.SAN.TİC.A.Ş.', belgeNo:'NYM2026000000003', siparisNo:'', tutar:28650,  status:'faturalasms', iptal:false,
      urunler:[{ ad:'30 x TİC-CNX CONEX 1 LT', birimFiyat:'795,833333', tutar:'28.650,00' }], kullanici:'DOĞTAR GIDA TARIM HAYV.İNŞ.SAN.TİC.A.Ş.' },
    { id:3, tarih:'14.04.2026', musteri:'ABC Teknoloji A.Ş.',                         belgeNo:'NYM2026000000001', siparisNo:'SİP-2026-001', tutar:18500,  status:'faturalasms', iptal:false,
      urunler:[{ ad:'5 x Yazılım Lisansı Pro', birimFiyat:'3.700,000000', tutar:'18.500,00' }], kullanici:'ABC Teknoloji A.Ş.' },
    { id:4, tarih:'12.04.2026', musteri:'Yıldız Market Ltd.',                         belgeNo:'NYM2026000000004', siparisNo:'', tutar:9750,   status:'bekliyor',   iptal:false,
      urunler:[{ ad:'10 x Ambalaj Seti', birimFiyat:'975,000000', tutar:'9.750,00' }], kullanici:'Yıldız Market Ltd.' },
    { id:5, tarih:'10.04.2026', musteri:'Demir İnşaat San.',                          belgeNo:'NYM2026000000005', siparisNo:'SİP-2026-002', tutar:32400,  status:'siparis',    iptal:false,
      urunler:[{ ad:'20 x Çimento Torbası', birimFiyat:'1.620,000000', tutar:'32.400,00' }], kullanici:'Demir İnşaat San.' },
    { id:6, tarih:'08.04.2026', musteri:'Güneş Otomotiv',                             belgeNo:'NYM2026000000006', siparisNo:'', tutar:4200,   status:'iptal',      iptal:true,
      urunler:[{ ad:'2 x Yağ Filtresi Seti', birimFiyat:'2.100,000000', tutar:'4.200,00' }], kullanici:'Güneş Otomotiv' },
  ];

  const STATUS_MAP = {
    faturalasms: { label:'Faturalaşmış', cls:'status-faturalasms' },
    bekliyor:    { label:'Bekliyor',     cls:'status-bekliyor' },
    siparis:     { label:'Sipariş',      cls:'status-siparis' },
    iptal:       { label:'İptal',        cls:'status-iptal' },
  };

  let filtered  = [...salesData];
  let expandedId = null;
  let sortCol   = 'tarih';
  let sortDir   = -1;
  const PER_PAGE = 15;
  let currentPage = 1;

  /* ════════ RENDER ════════ */
  function render() {
    const tbody = document.getElementById('salesTbody');
    const start = (currentPage - 1) * PER_PAGE;
    const page  = filtered.slice(start, start + PER_PAGE);

    if (!page.length) {
      tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-inbox"></i>Kayıt bulunamadı.</div></td></tr>`;
      renderPag(); return;
    }

    tbody.innerHTML = page.map(r => {
      const st  = STATUS_MAP[r.status] || { label:r.status, cls:'' };
      const exp = expandedId === r.id;
      const fmt = r.tutar.toLocaleString('tr-TR') + ',00 TL';

      const prodRows = r.urunler.map(p => `
        <tr>
          <td><div class="prod-thumb"><i class="fa-solid fa-image"></i></div></td>
          <td>${p.ad}</td>
          <td class="td-num">${p.birimFiyat}</td>
          <td class="td-num"><strong>${p.tutar}</strong></td>
        </tr>`).join('');

      return `
        <tr class="data-row${exp ? ' expanded' : ''}" data-id="${r.id}">
          <td class="td-toggle">
            <button class="row-toggle ${exp ? 'minus' : 'plus'}">${exp ? '−' : '+'}</button>
          </td>
          <td>${r.tarih}</td>
          <td><a class="cust-link" href="#">${r.musteri}</a></td>
          <td>${r.belgeNo}</td>
          <td>${r.siparisNo}</td>
          <td class="td-amount">${fmt}</td>
          <td><span class="status-badge ${st.cls}">${st.label}</span></td>
        </tr>
        <tr class="detail-row${exp ? ' open' : ''}" data-detail="${r.id}">
          <td colspan="7">
            <div class="detail-inner">
              <div class="detail-btns">
                <button class="btn-det red"   onclick="goSatis(${r.id})"><i class="fa-solid fa-cash-register"></i> Satış ekranına git</button>
                <button class="btn-det green" onclick="goMusteri(${r.id})"><i class="fa-solid fa-user"></i> Müşteri ekranına git</button>
                <button class="btn-det blue"  onclick="printRow(${r.id})"><i class="fa-solid fa-print"></i> Yazdır</button>
              </div>
              <table class="detail-prod-table">
                <thead>
                  <tr><th></th><th>Ürün</th><th style="text-align:right;">Birim Fiyat</th><th style="text-align:right;">Tutar (KDV Dahil)</th></tr>
                </thead>
                <tbody>${prodRows}</tbody>
              </table>
              <div class="detail-user-line">Kullanıcı : <strong>${r.kullanici}</strong></div>
            </div>
          </td>
        </tr>`;
    }).join('');

    /* Row click */
    tbody.querySelectorAll('.data-row').forEach(tr => {
      tr.addEventListener('click', e => {
        if (e.target.closest('a') || e.target.closest('button')) return;
        const id = parseInt(tr.dataset.id);
        expandedId = expandedId === id ? null : id;
        render();
      });
    });
    /* Toggle button click */
    tbody.querySelectorAll('.row-toggle').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        const id = parseInt(btn.closest('tr').dataset.id);
        expandedId = expandedId === id ? null : id;
        render();
      });
    });

    renderPag();
  }

  function renderPag() {
    const total = filtered.length;
    const pages = Math.ceil(total / PER_PAGE);
    const s = Math.min((currentPage - 1) * PER_PAGE + 1, total);
    const e = Math.min(currentPage * PER_PAGE, total);
    document.getElementById('pagInfo').textContent = total ? `${s}-${e} / ${total} kayıt` : '0 kayıt';

    const cont = document.getElementById('pagBtns');
    cont.innerHTML = '';
    const mkBtn = (html, dis, fn) => {
      const b = document.createElement('button');
      b.className = 'pag-btn'; b.innerHTML = html; b.disabled = dis;
      b.addEventListener('click', fn); return b;
    };
    cont.appendChild(mkBtn('<i class="fa-solid fa-chevron-left"></i>', currentPage===1, () => { currentPage--; render(); }));
    for (let i = 1; i <= pages; i++) {
      const b = mkBtn(i, false, () => { currentPage = i; render(); });
      if (i === currentPage) b.classList.add('active');
      cont.appendChild(b);
    }
    cont.appendChild(mkBtn('<i class="fa-solid fa-chevron-right"></i>', currentPage===pages||!pages, () => { currentPage++; render(); }));
  }

  /* ════════ FILTER ════════ */
  function applyFilter() {
    const q        = document.getElementById('searchTxt').value.trim();
    const showIp   = document.getElementById('iptalleriToggle').checked;
    filtered = salesData.filter(r => {
      if (!showIp && r.iptal) return false;
      if (q.length >= 3) {
        const type = document.getElementById('searchType').value;
        let hay = type === 'urun' ? r.urunler.map(u=>u.ad).join(' ') : r.musteri + ' ' + r.belgeNo;
        if (!hay.toLowerCase().includes(q.toLowerCase())) return false;
      }
      return true;
    });
    currentPage = 1; expandedId = null; render();
  }

  document.getElementById('searchTxt').addEventListener('input', applyFilter);
  document.getElementById('iptalleriToggle').addEventListener('change', applyFilter);
  document.getElementById('searchType').addEventListener('change', applyFilter);

  /* ════════ BELGE TİPİ DROPDOWN ════════ */
  const belgeTipiBtn  = document.getElementById('belgeTipiBtn');
  const belgeDrop     = document.getElementById('belgeDrop');
  belgeTipiBtn.addEventListener('click', e => { e.stopPropagation(); belgeDrop.classList.toggle('open'); belgeTipiBtn.classList.toggle('open'); });
  document.addEventListener('click', e => { if (!document.getElementById('belgeTipiWrap').contains(e.target)) { belgeDrop.classList.remove('open'); belgeTipiBtn.classList.remove('open'); } });
  document.getElementById('chkTumu').addEventListener('change', function () {
    document.querySelectorAll('.blg-chk').forEach(c => c.checked = this.checked);
    updateBelgeLabel();
  });
  document.querySelectorAll('.blg-chk').forEach(c => c.addEventListener('change', updateBelgeLabel));
  function updateBelgeLabel() {
    const all = document.querySelectorAll('.blg-chk');
    const chk = [...all].filter(c => c.checked);
    const NAMES = { siparis:'Sipariş', irsaliye:'İrsaliye', faturalasms:'Faturalaşmış', 'e-faturalasms':'E-Faturalaşmış', 'e-faturalasms-hayir':'E-Faturalaşmamış' };
    document.getElementById('belgeTipiLabel').textContent =
      (!chk.length || chk.length === all.length) ? 'Tüm Belge Tipleri' : chk.map(c => NAMES[c.value]||c.value).join(', ');
  }

  /* ════════ DÖNEM DROPDOWN ════════ */
  const periodBtn  = document.getElementById('periodBtn');
  const periodDrop = document.getElementById('periodDrop');
  periodBtn.addEventListener('click', e => { e.stopPropagation(); periodDrop.classList.toggle('open'); });
  document.addEventListener('click', e => { if (!document.getElementById('periodWrap').contains(e.target)) periodDrop.classList.remove('open'); });
  document.querySelectorAll('.period-item').forEach(item => {
    item.addEventListener('click', function () {
      document.querySelectorAll('.period-item').forEach(i => i.classList.remove('active'));
      this.classList.add('active');
      periodBtn.innerHTML = this.dataset.label + ' <i class="fa-solid fa-chevron-down"></i>';
      periodDrop.classList.remove('open');
    });
  });

  /* ════════ SORT ════════ */
  document.querySelectorAll('.th-sort').forEach(el => {
    el.addEventListener('click', function () {
      const col = this.dataset.col;
      sortDir = sortCol === col ? sortDir * -1 : 1;
      sortCol = col;
      filtered.sort((a, b) => {
        const va = a[col] ?? ''; const vb = b[col] ?? '';
        return va < vb ? -sortDir : va > vb ? sortDir : 0;
      });
      document.querySelectorAll('.sort-ic').forEach(i => i.className = 'fa-solid fa-sort sort-ic');
      this.querySelector('.sort-ic').className = `fa-solid fa-sort-${sortDir===1?'up':'down'} sort-ic`;
      currentPage = 1; render();
    });
  });

  /* ════════ ROW ACTIONS ════════ */
  const satisEkleUrl = '<?= BASE_URL ?>/satis/ekle';
  window.goSatis   = id => { window.location.href = `${satisEkleUrl}?id=${id}`; };
  window.goMusteri = id => { const r = salesData.find(x=>x.id===id); if(r) showToast(`"${r.musteri}" müşteri ekranına gidiliyor…`,'info'); };
  window.printRow  = id => { const r = salesData.find(x=>x.id===id); if(r) showToast(`${r.belgeNo} yazdırılıyor…`,'info'); };

  /* ════════ TOAST ════════ */
  function showToast(msg, type='success') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    const ic = {success:'check-circle',error:'circle-exclamation',info:'circle-info'};
    const cl = {success:'#22c55e',error:'#ef4444',info:'#3b82f6'};
    t.className = `toast-msg ${type}`;
    t.innerHTML = `<i class="fa-solid fa-${ic[type]}" style="color:${cl[type]};font-size:14px;"></i> ${msg}`;
    c.appendChild(t);
    setTimeout(()=>t.remove(), 3000);
  }

  /* ════════ SIDEBAR ACCORDION ════════ */
  document.querySelectorAll('.submenu').forEach(sub => {
    const link = document.querySelector(`[data-bs-target="#${sub.id}"]`);
    if (!link) return;
    sub.addEventListener('show.bs.collapse', () => {
      link.setAttribute('aria-expanded','true');
      document.querySelectorAll('.submenu.show').forEach(o => {
        if (o.id !== sub.id) bootstrap.Collapse.getInstance(o)?.hide();
      });
    });
    sub.addEventListener('hide.bs.collapse', () => link.setAttribute('aria-expanded','false'));
  });

  /* ── YENİ MÜŞTERİ MODAL AÇMA/KAPAMA VE YÖNLENDİRME ── */
  const ymOverlay = document.getElementById('ymOverlay');
  document.getElementById('btnYeniMusteri').addEventListener('click', () => {
    ymOverlay.classList.add('open');
    setTimeout(() => document.getElementById('ymIsim').focus(), 100);
  });
  document.getElementById('ymClose').addEventListener('click', () => ymOverlay.classList.remove('open'));
  document.getElementById('ymVazgec').addEventListener('click', () => ymOverlay.classList.remove('open'));
  ymOverlay.addEventListener('click', e => { if(e.target===ymOverlay) ymOverlay.classList.remove('open'); });

  document.getElementById('ymDevam').addEventListener('click', () => {
    const isim = document.getElementById('ymIsim').value.trim();
    if (!isim) {
      alert("Lütfen isim veya unvan giriniz.");
      return;
    }
    // Kayıtlı müşteri sayfasına (yeni isimle) yönlendir:
    window.location.href = satisEkleUrl + '?musteri=' + encodeURIComponent(isim);
  });

  /* ── KAYITLI MÜŞTERİ MODAL AÇ/KAPAT VE SEÇİM ── */
  const kmOverlay = document.getElementById('kmOverlay');
  const btnKayitliMusteri = document.getElementById('btnKayitliMusteri');
  if(btnKayitliMusteri) {
    btnKayitliMusteri.addEventListener('click', () => {
      kmOverlay.classList.add('open');
      setTimeout(()=> document.getElementById('kmSearch').focus(), 100);
    });
  }
  const kmClose = document.getElementById('kmClose');
  if (kmClose) kmClose.addEventListener('click', () => kmOverlay.classList.remove('open'));
  if (kmOverlay) kmOverlay.addEventListener('click', e => { if (e.target === kmOverlay) kmOverlay.classList.remove('open'); });

  document.querySelectorAll('.km-item').forEach(item => {
    item.addEventListener('mouseenter', () => {
      document.querySelectorAll('.km-item').forEach(i => { i.style.background = '#fff'; i.style.color = '#555'; });
      item.style.background = '#2f73b6'; item.style.color = '#fff';
    });
    item.addEventListener('click', () => {
      window.location.href = satisEkleUrl + '?musteri=' + encodeURIComponent(item.textContent.trim());
    });
  });

  /* ════════ INIT ════════ */

  render();

  // Close profile dropdown when clicking outside
  document.addEventListener('click', function(e) {
    const profileDropdown = document.getElementById('profileDropdown');
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    if (profileDropdown && userDropdownBtn && !profileDropdown.contains(e.target) && !userDropdownBtn.contains(e.target)) {
      profileDropdown.classList.remove('show');
    }
  });

})();
</script>
