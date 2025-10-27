import Sortable from 'sortablejs';

window.initKanban = function() {
    // Mevcut sortable instance'ları temizle
    document.querySelectorAll('.tasks-container').forEach(container => {
        if (container.sortable) {
            container.sortable.destroy();
        }
    });
    
    const listsContainer = document.querySelector('.lists-container');
    if (listsContainer?.sortable) {
        listsContainer.sortable.destroy();
    }

    // Liste sıralaması
    if (listsContainer) {
        new Sortable(listsContainer, {
            animation: 150,
            handle: '.list-handle',
            draggable: '.list-item',
            ghostClass: 'opacity-50',
            dragClass: 'bg-gray-100',
            forceFallback: true,
            onEnd: function(evt) {
                const lists = Array.from(evt.to.children)
                    .filter(el => el.classList.contains('list-item'))
                    .map((el, index) => ({
                        id: el.dataset.listId,
                        order: index
                    }));
                
                // Livewire'a event gönder
                const el = document.querySelector('[wire\\:id]');
                const component = Livewire.find(el.getAttribute('wire:id'));
                
                component.call('handleListReorder', lists);
            }
        });
    }

    // Görev sıralaması
    document.querySelectorAll('.tasks-container').forEach(container => {
        new Sortable(container, {
            group: 'tasks',
            animation: 150,
            handle: '.task-handle',
            draggable: '.task-item',
            ghostClass: 'opacity-50',
            dragClass: 'bg-gray-100',
            forceFallback: true,
            filter: '.non-draggable',
            onEnd: function(evt) {
                const targetListId = evt.to.closest('.list-item').dataset.listId;
                const tasks = Array.from(evt.to.children)
                    .filter(el => el.classList.contains('task-item'))
                    .map((el, index) => ({
                        id: el.dataset.taskId,
                        order: index
                    }));

                if (tasks.length && targetListId) {
                    // Livewire'a event gönder
                    const el = document.querySelector('[wire\\:id]');
                    const component = Livewire.find(el.getAttribute('wire:id'));
                    
                    component.call('handleTaskReorder', {
                        tasks: tasks,
                        targetListId: targetListId
                    });
                }
            }
        });
    });
};

// CSS stillerini ekle
const style = document.createElement('style');
style.textContent = `
    /* Marker'ları kaldır */
    .lists-container {
        list-style: none !important;
    }
    .lists-container > * {
        list-style: none !important;
    }
    .lists-container > *::marker {
        display: none !important;
    }
    
    .sortable-fallback {
        transform: rotate(3deg);
        opacity: 0.8;
        cursor: grabbing !important;
    }
    .task-item, .list-item {
        touch-action: none;
        cursor: grab;
    }
    .task-item:active, .list-item:active {
        cursor: grabbing;
    }
    .tasks-container {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        list-style: none !important;
    }
    .tasks-container > * {
        list-style: none !important;
    }
    .tasks-container > *::marker {
        display: none !important;
    }
    .tasks-container:empty::after {
        content: '';
        flex: 1;
        border: 2px dashed #e5e7eb;
        border-radius: 0.5rem;
        margin: 0.5rem;
    }
    .tasks-container.sortable-drag-active {
        background-color: rgba(243, 244, 246, 0.5);
    }
    
    /* Butonlar için z-index ve pointer-events ayarları */
    .action-buttons {
        position: relative;
        z-index: 10;
    }
    .action-buttons button {
        pointer-events: auto !important;
    }
    .non-draggable {
        pointer-events: auto !important;
    }
`;
document.head.appendChild(style); 