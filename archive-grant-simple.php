<?php get_header(); ?>

<!-- シンプル助成金検索システム -->
<div class="simple-grant-archive" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    
    <!-- ヘッダー -->
    <div class="archive-header" style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #333; margin-bottom: 10px;">助成金・補助金データベース</h1>
        <p style="color: #666;">シンプル検索システム（リセット版）</p>
    </div>
    
    <!-- 検索フォーム -->
    <div class="search-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
        <form id="unified-search-form" style="display: flex; gap: 10px; align-items: center;">
            <input 
                type="search" 
                id="search-keyword-input" 
                placeholder="助成金・補助金を検索..."
                style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;"
            />
            <button 
                type="submit" 
                id="search-btn"
                style="padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;"
            >
                検索
            </button>
        </form>
    </div>
    
    <!-- テストボタン -->
    <div class="test-section" style="text-align: center; margin-bottom: 20px;">
        <button onclick="simpleSearch.test()" style="padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">
            テスト検索実行
        </button>
        <button onclick="testConnection()" style="padding: 8px 16px; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">
            接続テスト
        </button>
    </div>
    
    <!-- 結果表示エリア -->
    <div id="grants-display" style="min-height: 200px;">
        <div style="text-align: center; color: #666; padding: 40px;">
            検索ボタンをクリックして助成金を検索してください
        </div>
    </div>
    
</div>

<script>
// 接続テスト関数
async function testConnection() {
    console.log('🔧 接続テスト開始');
    
    try {
        const response = await fetch(window.location.origin + '/ajax-handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=test_connection'
        });
        
        console.log('📡 レスポンス:', response.status);
        
        if (response.ok) {
            const data = await response.json();
            console.log('✅ 接続成功:', data);
            alert('接続テスト成功！詳細はコンソールを確認してください。');
        } else {
            throw new Error(`HTTP ${response.status}`);
        }
        
    } catch (error) {
        console.error('❌ 接続エラー:', error);
        alert('接続テスト失敗: ' + error.message);
    }
}

console.log('🚀 シンプル検索ページ読み込み完了');
console.log('💡 テスト方法:');
console.log('1. simpleSearch.test() - 検索テスト');
console.log('2. testConnection() - 接続テスト');
</script>

<!-- シンプル検索システムの読み込み -->
<script src="<?php echo get_template_directory_uri(); ?>/assets/js/simple-search.js"></script>

<?php get_footer(); ?>