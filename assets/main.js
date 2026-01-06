function toggleNavItem(button) {
    const navItem = button.closest('.nav-item');
    const navChildren = navItem.querySelector('.nav-children');
    
    if (navChildren) {
        navChildren.classList.toggle('expanded');
        button.classList.toggle('expanded');
    }
}

function toggleNavItemByTitle(titleElement, e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    const navItem = titleElement.closest('.nav-item');
    if (!navItem) return;
    
    const toggleButton = navItem.querySelector('.nav-toggle');
    const navChildren = navItem.querySelector('.nav-children');
    
    if (navChildren) {
        navChildren.classList.toggle('expanded');
        if (toggleButton) {
            toggleButton.classList.toggle('expanded');
        }
    }
}

window.toggleNavItem = toggleNavItem;
window.toggleNavItemByTitle = toggleNavItemByTitle;

document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (mobileMenuBtn && sidebar && sidebarOverlay) {
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            mobileMenuBtn.classList.toggle('active');
        }
        
        function closeSidebar() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            mobileMenuBtn.classList.remove('active');
        }
        
        let menuButtonTouched = false;
        const handleMenuClick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (e.type === 'touchstart') {
                menuButtonTouched = true;
                setTimeout(() => {
                    if (menuButtonTouched) {
                        toggleSidebar();
                        menuButtonTouched = false;
                    }
                }, 100);
            } else {
                if (!menuButtonTouched) {
                    toggleSidebar();
                }
                menuButtonTouched = false;
            }
        };
        
        mobileMenuBtn.addEventListener('touchstart', handleMenuClick, { passive: false });
        mobileMenuBtn.addEventListener('click', handleMenuClick);
        
        sidebarOverlay.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('touchend', closeSidebar);
        
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', closeSidebar);
        });
    }
    
    const mobileTocBtn = document.getElementById('mobileTocBtn');
    const mobileTocPanel = document.getElementById('mobileTocPanel');
    const mobileTocClose = document.getElementById('mobileTocClose');
    const tocOverlay = document.getElementById('tocOverlay');
    
    if (mobileTocBtn && mobileTocPanel) {
        function toggleToc() {
            mobileTocPanel.classList.toggle('active');
            if (tocOverlay) {
                tocOverlay.classList.toggle('active');
            }
            mobileTocBtn.classList.toggle('active');
        }
        
        function closeToc() {
            mobileTocPanel.classList.remove('active');
            if (tocOverlay) {
                tocOverlay.classList.remove('active');
            }
            mobileTocBtn.classList.remove('active');
        }
        
        let tocButtonTouched = false;
        const handleTocClick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (e.type === 'touchstart') {
                tocButtonTouched = true;
                setTimeout(() => {
                    if (tocButtonTouched) {
                        toggleToc();
                        tocButtonTouched = false;
                    }
                }, 100);
            } else {
                if (!tocButtonTouched) {
                    toggleToc();
                }
                tocButtonTouched = false;
            }
        };
        
        mobileTocBtn.addEventListener('touchstart', handleTocClick, { passive: false });
        mobileTocBtn.addEventListener('click', handleTocClick);
        
        if (mobileTocClose) {
            let tocCloseTouched = false;
            const handleTocClose = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (e.type === 'touchstart') {
                    tocCloseTouched = true;
                    setTimeout(() => {
                        if (tocCloseTouched) {
                            closeToc();
                            tocCloseTouched = false;
                        }
                    }, 100);
                } else {
                    if (!tocCloseTouched) {
                        closeToc();
                    }
                    tocCloseTouched = false;
                }
            };
            mobileTocClose.addEventListener('touchstart', handleTocClose, { passive: false });
            mobileTocClose.addEventListener('click', handleTocClose);
        }
        
        if (tocOverlay) {
            tocOverlay.addEventListener('click', closeToc);
            tocOverlay.addEventListener('touchend', closeToc);
        }
        
        const tocLinks = mobileTocPanel.querySelectorAll('.toc-list a');
        tocLinks.forEach(link => {
            link.addEventListener('click', closeToc);
        });
        
        mobileTocPanel.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    const allNavChildren = document.querySelectorAll('.nav-children');
    const allNavToggles = document.querySelectorAll('.nav-toggle');
    
    allNavChildren.forEach(children => {
        children.classList.add('expanded');
    });
    
    allNavToggles.forEach(toggle => {
        toggle.classList.add('expanded');
    });
    
    const docBody = document.querySelector('.doc-body');
    if (docBody) {
        const headings = docBody.querySelectorAll('h1, h2, h3, h4, h5, h6');
        headings.forEach((heading, index) => {
            if (!heading.id) {
                const text = heading.textContent.trim();
                const id = 'heading-' + text.replace(/\s+/g, '-').toLowerCase().replace(/[^\w-]/g, '') + '-' + index;
                heading.id = id;
            }
        });
        
        const tocLinks = document.querySelectorAll('.toc-list a');
        tocLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && href.startsWith('#')) {
                const targetId = href.substring(1);
                const targetHeading = document.getElementById(targetId);
                if (targetHeading && !targetHeading.id) {
                    targetHeading.id = targetId;
                }
            }
        });
        
        tocLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });
        
        const observerOptions = {
            root: null,
            rootMargin: '-20% 0px -70% 0px',
            threshold: 0
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    tocLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === '#' + id) {
                            link.classList.add('active');
                            link.style.color = '#2563eb';
                            link.style.fontWeight = '500';
                        }
                    });
                }
            });
        }, observerOptions);
        
        headings.forEach(heading => {
            if (heading.id) {
                observer.observe(heading);
            }
        });
    }
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('nav-group-title') && e.target.tagName === 'SPAN') {
            e.preventDefault();
            e.stopPropagation();
            toggleNavItemByTitle(e.target, e);
        }
    });
    
    const searchModal = document.getElementById('searchModal');
    const searchInput = document.getElementById('searchModalInput');
    const searchResults = document.getElementById('searchResults');
    const searchCloseBtn = document.getElementById('searchCloseBtn');
    const headerSearchBox = document.querySelector('.header-left .search-input');
    const searchBoxInput = headerSearchBox || document.querySelector('.search-input');
    
    let currentSelectedIndex = -1;
    let searchData = [];
    let projectSlug = '';
    
    const pathParts = window.location.pathname.split('/').filter(p => p);
    if (pathParts.length > 0 && pathParts[0] !== 'admin' && pathParts[0] !== 'api' && pathParts[0] !== 'assets') {
        projectSlug = pathParts[0];
    }
    
    function openSearchModal() {
        if (!searchModal || !searchInput || !searchResults) return;
        
        searchResults.innerHTML = '<div class="search-empty">输入关键词搜索章节</div>';
        searchModal.style.display = 'flex';
        
        setTimeout(() => {
            if (searchInput) {
                searchInput.focus();
            }
        }, 100);
    }
    
    function closeSearchModal() {
        if (!searchModal) return;
        searchModal.style.display = 'none';
        searchInput.value = '';
        searchResults.innerHTML = '<div class="search-empty">输入关键词搜索章节</div>';
        currentSelectedIndex = -1;
    }
    
    let searchTimeout;
    function performSearch(query) {
        if (!query.trim()) {
            searchResults.innerHTML = '<div class="search-empty">输入关键词搜索章节</div>';
            currentSelectedIndex = -1;
            return;
        }
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchUrl = projectSlug 
                ? `/api/search.php?q=${encodeURIComponent(query)}&project=${encodeURIComponent(projectSlug)}`
                : `/api/search.php?q=${encodeURIComponent(query)}`;
            
            fetch(searchUrl)
                .then(response => response.json())
                .then(data => {
                    searchData = data.results || [];
                    renderSearchResults(searchData);
                })
                .catch(error => {
                    searchResults.innerHTML = '<div class="search-empty">搜索出错，请稍后重试</div>';
                });
        }, 300);
    }
    
    function renderSearchResults(results) {
        if (results.length === 0) {
            searchResults.innerHTML = '<div class="search-empty">未找到相关章节</div>';
            currentSelectedIndex = -1;
            return;
        }
        
        let html = '<div class="search-results-list">';
        results.forEach((item, index) => {
            const isSelected = index === currentSelectedIndex;
            
            let displayPath = item.path || '';
            
            if (projectSlug) {
                if (item.project_name && displayPath.includes(' > ')) {
                    const parts = displayPath.split(' > ');
                    if (parts.length > 1 && parts[0] === item.project_name) {
                        displayPath = parts.slice(1).join(' > ');
                    }
                }
            }
            
            html += `<div class="search-result-item ${isSelected ? 'selected' : ''}" data-index="${index}" data-project-slug="${item.project_slug || ''}" data-slug="${item.slug || ''}">
                <div class="search-result-content">
                    <div class="search-result-path">${escapeHtml(displayPath)}</div>
                </div>
            </div>`;
        });
        html += '</div>';
        searchResults.innerHTML = html;
        
        const resultItems = searchResults.querySelectorAll('.search-result-item');
        resultItems.forEach((item, index) => {
            item.addEventListener('click', function() {
                selectResult(index);
            });
        });
        
        if (currentSelectedIndex >= 0 && currentSelectedIndex < results.length) {
            const selectedItem = searchResults.querySelector(`[data-index="${currentSelectedIndex}"]`);
            if (selectedItem) {
                selectedItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function selectResult(index) {
        if (index >= 0 && index < searchData.length) {
            const item = searchData[index];
            const targetProjectSlug = item.project_slug || projectSlug;
            if (targetProjectSlug) {
                window.location.href = `/${targetProjectSlug}/${item.slug}`;
            }
        }
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value;
            performSearch(query);
            currentSelectedIndex = -1;
        });
        
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentSelectedIndex = Math.min(currentSelectedIndex + 1, searchData.length - 1);
                renderSearchResults(searchData);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentSelectedIndex = Math.max(currentSelectedIndex - 1, -1);
                renderSearchResults(searchData);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (currentSelectedIndex >= 0) {
                    selectResult(currentSelectedIndex);
                } else if (searchData.length > 0) {
                    selectResult(0);
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                closeSearchModal();
            }
        });
    }
    
    if (searchCloseBtn) {
        searchCloseBtn.addEventListener('click', closeSearchModal);
    }
    
    if (searchModal) {
        searchModal.addEventListener('click', function(e) {
            if (e.target === searchModal) {
                closeSearchModal();
            }
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey && e.key === 'k') || (e.key === 'q' && !e.ctrlKey && !e.altKey && !e.shiftKey)) {
            const activeElement = document.activeElement;
            if (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA') {
                e.preventDefault();
                openSearchModal();
            }
        }
    });
    
    let finalSearchBox = searchBoxInput;
    if (!finalSearchBox) {
        finalSearchBox = document.querySelector('.header-left .search-input') || document.querySelector('.search-input');
    }
    
    if (finalSearchBox) {
        finalSearchBox.style.pointerEvents = 'auto';
        finalSearchBox.style.cursor = 'pointer';
        finalSearchBox.readOnly = true;
        
        const handleSearchClick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            openSearchModal();
        };
        
        finalSearchBox.addEventListener('click', function(e) {
            handleSearchClick(e);
        }, false);
        
        finalSearchBox.addEventListener('focus', function(e) {
            handleSearchClick(e);
        }, false);
        
        finalSearchBox.addEventListener('mousedown', function(e) {
            e.preventDefault();
            openSearchModal();
        }, false);
        
        finalSearchBox.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                e.stopPropagation();
                openSearchModal();
            }
        }, false);
    }
    
    // 名片弹窗功能
    const cardPopupModal = document.getElementById('cardPopupModal');
    const cardPopupOverlay = document.getElementById('cardPopupOverlay');
    const cardPopupClose = document.getElementById('cardPopupClose');
    const cardPopupContent = document.getElementById('cardPopupContent');
    const cardPopupBody = document.getElementById('cardPopupBody');
    const cardPopupImage = document.getElementById('cardPopupImage');
    const cardPopupIframe = document.getElementById('cardPopupIframe');
    const cardIconButtons = document.querySelectorAll('.card-icon-btn[data-popup="1"]');
    
    function isImageUrl(url) {
        const imageExtensions = /\.(jpg|jpeg|png|gif|webp|svg|bmp|ico)(\?.*)?$/i;
        return imageExtensions.test(url);
    }
    
    function openCardPopup(url) {
        const modal = document.getElementById('cardPopupModal');
        const content = document.getElementById('cardPopupContent');
        const body = document.getElementById('cardPopupBody');
        const image = document.getElementById('cardPopupImage');
        const iframe = document.getElementById('cardPopupIframe');
        
        if (!modal || !content || !body) {
            return;
        }
        
        const isImage = isImageUrl(url);
        
        if (isImage) {
            if (image) {
                image.src = url;
                image.style.display = 'block';
            }
            if (iframe) {
                iframe.style.display = 'none';
            }
            content.className = 'card-popup-content image-content';
            body.className = 'card-popup-body image-body';
        } else {
            if (image) {
                image.style.display = 'none';
            }
            if (iframe) {
                iframe.style.display = 'block';
                iframe.src = url;
            }
            content.className = 'card-popup-content iframe-content';
            body.className = 'card-popup-body iframe-body';
        }
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeCardPopup() {
        if (!cardPopupModal) return;
        
        cardPopupModal.classList.remove('active');
        
        if (cardPopupImage) {
            cardPopupImage.src = '';
            cardPopupImage.style.display = 'none';
        }
        if (cardPopupIframe) {
            cardPopupIframe.src = '';
            cardPopupIframe.style.display = 'none';
        }
        
        document.body.style.overflow = '';
    }
    
    document.addEventListener('click', function(e) {
        const cardBtn = e.target.closest ? e.target.closest('.card-icon-btn[data-popup="1"]') : null;
        
        if (cardBtn) {
            e.preventDefault();
            e.stopPropagation();
            const url = cardBtn.getAttribute('data-link');
            if (url) {
                openCardPopup(url);
            }
        }
    });
    
    if (cardPopupClose) {
        cardPopupClose.addEventListener('click', closeCardPopup);
    }
    
    if (cardPopupOverlay) {
        cardPopupOverlay.addEventListener('click', closeCardPopup);
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && cardPopupModal && cardPopupModal.classList.contains('active')) {
            closeCardPopup();
        }
    });
    
    // 移动端名片展开/收起功能
    const mobileCardsToggle = document.getElementById('mobileCardsToggle');
    const mobileCardsPanel = document.getElementById('mobileCardsPanel');
    
    if (mobileCardsToggle && mobileCardsPanel) {
        function toggleMobileCards() {
            mobileCardsToggle.classList.toggle('active');
            mobileCardsPanel.classList.toggle('active');
        }
        
        function closeMobileCards() {
            mobileCardsToggle.classList.remove('active');
            mobileCardsPanel.classList.remove('active');
        }
        
        mobileCardsToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMobileCards();
        });
        
        // 点击面板外部关闭
        document.addEventListener('click', function(e) {
            if (mobileCardsPanel && mobileCardsPanel.classList.contains('active')) {
                if (!mobileCardsPanel.contains(e.target) && !mobileCardsToggle.contains(e.target)) {
                    closeMobileCards();
                }
            }
        });
        
        // 点击名片项后关闭面板
        const mobileCardItems = mobileCardsPanel.querySelectorAll('.mobile-card-item');
        mobileCardItems.forEach(function(item) {
            item.addEventListener('click', function() {
                setTimeout(function() {
                    closeMobileCards();
                }, 200);
            });
        });
    }
});
