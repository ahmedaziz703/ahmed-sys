import 'flowbite';
import 'sortablejs';
import Chart from 'chart.js/auto';

// Global tanımlama
window.global = window;
window.Chart = Chart;

// تاريخ formatı yardımcı fonksiyonu
window.formatDateToYYYYMMDD = function(dateStr) {
    if (!dateStr) return '';
    
    try {
        // Eğer تاريخ zaten yyyy-MM-dd formatındaysa dokunma
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
            return dateStr;
        }
        
        // تاريخ nesnesine çevir
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) {
            console.error('Geçersiz تاريخ:', dateStr);
            return dateStr; // Geçersiz تاريخ, olduğu gibi döndür
        }
        
        // yyyy-MM-dd formatına çevir
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        return `${year}-${month}-${day}`;
    } catch (error) {
        console.error('تاريخ formatı düzeltme hatası:', error);
        return dateStr; // Hata الحالهunda orijinal değeri döndür
    }
};

// Sidebar yönetimi için global state
let sidebarOpen = false;

// Sidebar toggle fonksiyonu
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.getElementById('toggleSidebarMobileHamburger');
    const close = document.getElementById('toggleSidebarMobileClose');
    
    sidebarOpen = !sidebarOpen;
    sidebar.classList.toggle('-translate-x-full');
    hamburger.classList.toggle('hidden');
    close.classList.toggle('hidden');
}

// Sidebar event listeners'ları جديدden ekle
function initSidebarListeners() {
    const toggleButton = document.getElementById('toggleSidebarMobile');
    
    // Önceki event listener'ı kaldır
    toggleButton?.removeEventListener('click', handleToggleClick);
    
    // جديد event listener ekle
    toggleButton?.addEventListener('click', handleToggleClick);
}

// Toggle click handler
function handleToggleClick(e) {
    e.stopPropagation();
    toggleSidebar();
}

// Click outside handler
function handleClickOutside(event) {
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.getElementById('toggleSidebarMobile');

    if (sidebar && !sidebar.contains(event.target) && !toggleButton?.contains(event.target)) {
        if (!sidebar.classList.contains('-translate-x-full') && window.innerWidth < 1024) {
            toggleSidebar();
        }
    }
}

// Sayfa ilk yüklendiğinde
document.addEventListener('DOMContentLoaded', () => {
    initSidebarListeners();
    document.addEventListener('click', handleClickOutside);
});

// Livewire navigasyonlarında
document.addEventListener('livewire:navigated', () => {
    console.log('Livewire navigasyon olayı tetiklendi');
    
    // Mobilde sayfa geçişlerinde sidebar'ı kapat
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth < 1024 && !sidebar.classList.contains('-translate-x-full')) {
        toggleSidebar();
    }
    
    // Event listener'ları جديدden ekle
    initSidebarListeners();

    // Livewire state'ini koru ve جديدden başlat
    if (typeof Livewire !== 'undefined') {


        // Tüm JavaScript event listener'ları جديدden ekle
        document.querySelectorAll('[x-data]').forEach(element => {
            if (typeof Alpine !== 'undefined') {
                Alpine.initTree(element);
            }
        });
    }
});

// ESC tuşu ile kapatma
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        if (window.innerWidth < 1024 && !sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
        }
    }
});

// Flowbite'ı başlatma fonksiyonu
function initFlowbite() {
    const dropdownButtons = document.querySelectorAll('[data-dropdown-toggle]');
    dropdownButtons.forEach(button => {
        const targetId = button.getAttribute('data-dropdown-toggle');
        const target = document.getElementById(targetId);
        
        if (button && target) {
            const dropdown = new Dropdown(target, button);
            dropdown.init();
        }
    });
}

// نقدًا akışı grafiği için global değişkenler
window.cashFlowChart = null;

// نقدًا akışı grafiğini أنشاءma fonksiyonu
window.createCashFlowChart = function(forceRefresh = false) {
    console.log('createCashFlowChart çağrıldı, forceRefresh:', forceRefresh);
    
    // Canvas elementini kontrol et - 5 kez deneme yap
    let ctx = document.getElementById('cash-flow-chart');
    let attempts = 0;
    
    // Canvas bulunamazsa, kısa bir süre bekleyip tekrar dene
    if (!ctx) {
        console.log('Grafik canvas elementi لم يتم العثور عليه, tekrar deneniyor...');
        const checkCanvas = setInterval(() => {
            ctx = document.getElementById('cash-flow-chart');
            attempts++;
            
            if (ctx || attempts >= 5) {
                clearInterval(checkCanvas);
                if (!ctx) {
                    console.log('Grafik canvas elementi 5 denemeden sonra لم يتم العثور عليه');
                    return;
                }
                // Canvas bulundu, grafiği أنشاء
                initializeChart(ctx, forceRefresh);
            }
        }, 100);
        return;
    }
    
    // Grafiği başlatma fonksiyonu
    function initializeChart(ctx, forceRefresh) {
        // Livewire bileşenini kontrol et
        const livewireComponent = window.Livewire.find(ctx.closest('[wire\\:id]')?.getAttribute('wire:id'));
        if (!livewireComponent) {
            console.log('Livewire bileşeni لم يتم العثور عليه');
            return;
        }
        
        // Önceki grafiği temizle
        if (window.cashFlowChart instanceof Chart) {
            if (!forceRefresh) {
                console.log('Grafik zaten var ve forceRefresh false, güncelleme yapılmıyor');
                // Yükleme göstergesini gizle
                document.dispatchEvent(new CustomEvent('chartRendered'));
                return;
            }
            window.cashFlowChart.destroy();
            window.cashFlowChart = null;
        }
        
        try {
            // Veri hazırlama
            const chartData = livewireComponent.chartData || {};
            const labels = chartData.labels || [];
            const inflowData = chartData.inflowData || [];
            const outflowData = chartData.outflowData || [];
            const netData = chartData.netData || [];
            const chartType = livewireComponent.chartType || 'line';
            
            // Veri yoksa çık
            if (labels.length === 0) {
                console.log('Grafik için veri لم يتم العثور عليه');
                return;
            }
            
            // Grafik tipini göster
            const chartTypeText = {
                'line': 'خطي',
                'bar': 'عمودي',
                'stacked': 'مكدس'
            }[chartType] || chartType;
            
            const chartTypeElement = document.getElementById('current-chart-type');
            if (chartTypeElement) {
                chartTypeElement.textContent = 'نوع الرسم البياني: ' + chartTypeText;
            }
            
            console.log('Grafik أنشاءuluyor, tip:', chartType, 'veri sayısı:', labels.length);
            
            // Grafik konfigürasyonu
            const config = {
                type: chartType === 'stacked' ? 'bar' : chartType,
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'ايراد',
                            data: inflowData,
                            backgroundColor: 'rgba(74, 222, 128, 0.5)',
                            borderColor: 'rgb(74, 222, 128)',
                            borderWidth: 2,
                            tension: 0.1
                        },
                        {
                            label: 'مصروف',
                            data: outflowData,
                            backgroundColor: 'rgba(248, 113, 113, 0.5)',
                            borderColor: 'rgb(248, 113, 113)',
                            borderWidth: 2,
                            tension: 0.1
                        },
                        {
                            label: 'صافي التدفق النقدي',
                            data: netData,
                            backgroundColor: 'rgba(96, 165, 250, 0.5)',
                            borderColor: 'rgb(96, 165, 250)',
                            borderWidth: 2,
                            tension: 0.1
                            // Net نقدًا akışını tüm grafik tiplerinde göster
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 500 // Animasyon süresini kısalt
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('tr-TR', { 
                                            style: 'currency', 
                                            currency: 'YER',
                                            minimumFractionDigits: 2
                                        }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        },
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false,
                            text: 'نقدًا Akışı Analizi'
                        }
                    },
                    scales: {
                        x: {
                            stacked: chartType === 'stacked',
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: chartType === 'stacked',
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('tr-TR', { 
                                        style: 'currency', 
                                        currency: 'YER',
                                        maximumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            };
            
            // Grafiği أنشاء
            window.cashFlowChart = new Chart(ctx, config);
            console.log('Grafik başarıyla أنشاءuldu');
            
            // Yükleme göstergesini gizle
            document.dispatchEvent(new CustomEvent('chartRendered'));
        } catch (error) {
            console.error('Grafik أنشاءulurken hata:', error);
            // Hata الحالهunda da yükleme göstergesini gizle
            document.dispatchEvent(new CustomEvent('chartRendered'));
        }
    }
    
    // Canvas bulundu, hemen grafiği أنشاء
    initializeChart(ctx, forceRefresh);
};

// Livewire başlatma
document.addEventListener('livewire:initialized', () => {
    console.log('Livewire initialized, olaylar dinleniyor');
    
    // cashFlowDataUpdated eventi için dinleyici
    Livewire.on('cashFlowDataUpdated', () => {
        console.log('cashFlowDataUpdated eventi alındı');
        setTimeout(() => {
            window.createCashFlowChart(true);
        }, 100);
    });
    
    // Livewire bileşeni güncellendiğinde
    Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
        succeed(({ snapshot, effect }) => {
            // نقدًا akışı grafiği için kontrol
            const cashFlowElement = document.getElementById('cash-flow-chart');
            if (cashFlowElement && component.id === cashFlowElement.closest('[wire\\:id]')?.getAttribute('wire:id')) {
                console.log('نقدًا akışı bileşeni güncellendi');
                setTimeout(() => {
                    window.createCashFlowChart(true);
                }, 100);
            }

            // Proje board'ları için kontrol
            const boardElement = document.querySelector('[data-board]');
            if (boardElement && component.id === boardElement.closest('[wire\\:id]')?.getAttribute('wire:id')) {
                console.log('Proje board bileşeni güncellendi');
                // Sortable.js'i جديدden başlat
                if (typeof Sortable !== 'undefined') {
                    window.initKanban();
                }
            }
        });
    });

    // Livewire navigasyon olaylarını dinle
    Livewire.on('navigated', () => {
        console.log('Livewire navigasyon olayı alındı');
        // Tüm Livewire bileşenlerini جديدden başlat
        window.Livewire.components.forEach(component => {
            if (component.$wire) {
                component.$wire.resume();
            }
        });

        // Sortable.js'i جديدden başlat
        if (typeof Sortable !== 'undefined') {
            window.initKanban();
        }
    });
});

// Sayfa yüklendiğinde grafiği أنشاء
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded event tetiklendi');
    setTimeout(() => {
        const cashFlowElement = document.getElementById('cash-flow-chart');
        if (cashFlowElement) {
            console.log('نقدًا akışı grafiği bulundu, أنشاءuluyor');
            window.createCashFlowChart(true);
        }
    }, 500);
});

// Livewire navigasyonlarında grafiği جديدden أنشاء
document.addEventListener('livewire:navigated', () => {
    console.log('livewire:navigated event tetiklendi');
    setTimeout(() => {
        const cashFlowElement = document.getElementById('cash-flow-chart');
        if (cashFlowElement) {
            console.log('نقدًا akışı grafiği bulundu, جديدden أنشاءuluyor');
            window.createCashFlowChart(true);
        }
    }, 500);
});

