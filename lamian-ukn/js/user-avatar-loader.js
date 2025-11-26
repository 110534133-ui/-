// /lamian-ukn/js/user-avatar-loader.js
// 通用頭像載入模組 - 在所有頁面自動載入用戶頭像

(function() {
  'use strict';
  
  const API_BASE = '/lamian-ukn/api';
  
  /**
   * 從 API 載入用戶資訊並更新頭像
   */
  async function loadUserAvatar() {
    try {
      console.log('🔄 開始載入用戶頭像...');
      
      const response = await fetch(API_BASE + '/me.php', {
        credentials: 'include',
        cache: 'no-cache' // 不使用快取，確保取得最新資料
      });
      
      if (!response.ok) {
        if (response.status === 401) {
          console.warn('❌ 未登入，跳轉到登入頁');
          // 可選：跳轉到登入頁
          // window.location.href = '/lamian-ukn/login.php';
          return;
        }
        throw new Error(`HTTP ${response.status}`);
      }
      
      const data = await response.json();
      
      // 檢查是否有頭像 URL
      if (data.avatar_url) {
        // 加上時間戳避免瀏覽器快取
        const timestamp = new Date().getTime();
        const avatarUrl = data.avatar_url.includes('?') 
          ? `${data.avatar_url}&v=${timestamp}`
          : `${data.avatar_url}?v=${timestamp}`;
        
        // 更新所有頭像元素
        updateAvatarElements(avatarUrl);
        console.log('✅ 頭像已更新:', avatarUrl);
      } else {
        console.log('ℹ️ 用戶未設定頭像');
      }
      
      // 更新用戶名稱（如果需要）
      if (data.name) {
        updateUserNameElements(data.name);
      }
      
    } catch (error) {
      console.error('❌ 載入頭像失敗:', error);
    }
  }
  
  /**
   * 更新頁面中所有頭像元素
   */
  function updateAvatarElements(avatarUrl) {
    // 更新導航欄頭像
    const navAvatar = document.querySelector('.navbar .user-avatar');
    if (navAvatar) {
      navAvatar.src = avatarUrl;
      console.log('  ✓ 導航欄頭像已更新');
    }
    
    // 更新其他可能的頭像位置
    const allAvatars = document.querySelectorAll('.user-avatar');
    allAvatars.forEach((avatar, index) => {
      avatar.src = avatarUrl;
      console.log(`  ✓ 頭像 ${index + 1} 已更新`);
    });
    
    // 更新帳號設置頁面的大頭像
    const avatarImg = document.getElementById('avatarImg');
    if (avatarImg) {
      avatarImg.src = avatarUrl;
      console.log('  ✓ 大頭像已更新');
    }
  }
  
  /**
   * 更新頁面中所有用戶名稱元素
   */
  function updateUserNameElements(userName) {
    const navUserName = document.getElementById('navUserName');
    if (navUserName) {
      navUserName.textContent = userName;
    }
    
    const loggedAs = document.getElementById('loggedAs');
    if (loggedAs) {
      loggedAs.textContent = userName;
    }
  }
  
  /**
   * 初始化：頁面載入時自動執行
   */
  function init() {
    // 如果 DOM 已經載入完成
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', loadUserAvatar);
    } else {
      // DOM 已經載入，直接執行
      loadUserAvatar();
    }
    
    // 監聽 storage 事件（跨頁籤同步）
    window.addEventListener('storage', function(e) {
      if (e.key === 'avatar_updated') {
        console.log('🔄 偵測到頭像更新，重新載入...');
        loadUserAvatar();
      }
    });
  }
  
  // 將函數暴露到全域，方便其他腳本呼叫
  window.userAvatarLoader = {
    load: loadUserAvatar,
    updateAvatar: updateAvatarElements,
    updateUserName: updateUserNameElements
  };
  
  // 自動初始化
  init();
  
})();