/**
 * シンプル検索システム - 完全リセット版
 * 最小限の機能で確実に動作
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    console.log('🔄 シンプル検索システム開始');
    
    // 基本設定 - フォールバック対応
    const config = {
        // 第1選択: 独立エンドポイント
        independentUrl: window.location.origin + '/ajax-handler.php',
        // 第2選択: WordPress AJAX
        wpAjaxUrl: window.location.origin + '/wp-admin/admin-ajax.php',
        debug: true
    };
    
    // DOM要素
    const elements = {
        searchForm: document.getElementById('unified-search-form') || document.querySelector('form'),
        searchInput: document.getElementById('search-keyword-input') || document.querySelector('input[type="search"]'),
        searchButton: document.getElementById('search-btn') || document.querySelector('button[type="submit"]'),
        resultsContainer: document.getElementById('grants-display') || document.querySelector('.grants-container')
    };
    
    // デバッグ: 要素確認
    console.log('🔍 DOM要素確認:', elements);
    
    // シンプル検索関数
    async function performSearch(query = '') {
        console.log('🔍 検索実行:', query);
        
        // ローディング表示
        showLoading();
        
        try {
            const response = await fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=search_grants&search=${encodeURIComponent(query)}`
            });
            
            console.log('📡 レスポンス:', response.status, response.statusText);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('📄 データ受信:', data);
            
            if (data.success) {
                displayResults(data.data);
            } else {
                showError(data.message || '検索に失敗しました');
            }
            
        } catch (error) {
            console.error('❌ 検索エラー:', error);
            showError('検索中にエラーが発生しました: ' + error.message);
        } finally {
            hideLoading();
        }
    }
    
    // 結果表示
    function displayResults(data, source = '') {
        if (!elements.resultsContainer) {
            console.error('結果表示エリアが見つかりません');
            return;
        }
        
        let html = `
            <div class="search-results">
                <h2>${source} 検索結果: ${data.total}件</h2>
                <div class="grants-list">
        `;
        
        if (data.grants && data.grants.length > 0) {
            data.grants.forEach(grant => {
                html += `
                    <div class="grant-item" style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: white;">
                        <h3><a href="${grant.permalink}" target="_blank">${grant.title}</a></h3>
                        <p>${grant.excerpt}</p>
                        <div class="grant-meta">
                            <span>金額: ${grant.meta.max_amount || 'N/A'}</span> | 
                            <span>組織: ${grant.meta.organization || 'N/A'}</span>
                        </div>
                        <small>ID: ${grant.id} | 投稿日: ${grant.date}</small>
                    </div>
                `;
            });
        } else {
            html += '<p>該当する助成金が見つかりませんでした。</p>';
        }
        
        html += '</div></div>';
        elements.resultsContainer.innerHTML = html;
    }
    
    // ローディング表示
    function showLoading() {
        if (elements.resultsContainer) {
            elements.resultsContainer.innerHTML = '<div style="text-align: center; padding: 20px;">検索中...</div>';
        }
    }
    
    // ローディング非表示
    function hideLoading() {
        // displayResultsで上書きされるため、特に処理不要
    }
    
    // エラー表示
    function showError(message) {
        if (elements.resultsContainer) {
            elements.resultsContainer.innerHTML = `
                <div style="color: red; border: 1px solid red; padding: 15px; margin: 10px 0; background: #fff5f5;">
                    <strong>エラー:</strong> ${message}
                </div>
            `;
        }
    }
    
    // イベントリスナー設定
    function setupEvents() {
        // 検索フォーム送信
        if (elements.searchForm) {
            elements.searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const query = elements.searchInput ? elements.searchInput.value.trim() : '';
                performSearch(query);
            });
        }
        
        // 検索ボタンクリック
        if (elements.searchButton) {
            elements.searchButton.addEventListener('click', function(e) {
                e.preventDefault();
                const query = elements.searchInput ? elements.searchInput.value.trim() : '';
                performSearch(query);
            });
        }
        
        console.log('✅ イベントリスナー設定完了');
    }
    
    // 初期化
    setupEvents();
    
    // テスト用関数をグローバルに公開
    window.simpleSearch = {
        search: performSearch,
        test: () => performSearch('助成金'),
        config: config,
        elements: elements
    };
    
    console.log('✅ シンプル検索システム初期化完了');
    console.log('💡 テスト実行: simpleSearch.test()');
});