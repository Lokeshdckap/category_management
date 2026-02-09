<template>
  <div>
    <!-- Main Category Item -->
    <q-item 
      class="reorder-item q-mb-sm" 
      :style="{ 
        marginLeft: (level * 40) + 'px',
        borderLeft: level > 0 ? '3px solid #e0e0e0' : 'none'
      }"
    >
      <q-item-section side class="drag-handle cursor-move">
        <q-icon name="drag_indicator" color="grey-6" size="24px" />
      </q-item-section>

      <q-item-section>
        <q-item-label>
          <span v-if="level > 0" class="text-grey-6 q-mr-sm">└─</span>
          
          <strong>{{ displayIndex }}.</strong> {{ element.name }}
          
          <q-badge 
            v-if="element.featured" 
            color="positive" 
            class="q-ml-sm"
          >
            Featured
          </q-badge>
          
          <q-badge 
            v-if="localChildren.length > 0" 
            color="blue-grey-5" 
            class="q-ml-sm"
          >
            {{ localChildren.length }} sub
          </q-badge>
        </q-item-label>
        
        <q-item-label caption>
          Slug: {{ element.slug }}
        </q-item-label>
      </q-item-section>

      <q-item-section side>
        <div class="text-caption text-grey-7">
          Order: {{ viewMode === 'featured' ? element.featured_order : element.sort_order }}
        </div>
      </q-item-section>
    </q-item>

    <!-- Render children recursively with draggable -->
    <draggable
      v-if="localChildren.length > 0"
      v-model="localChildren"
      item-key="uuid"
      handle=".drag-handle"
      :group="{ name: 'categories' }"
      class="children-container"
      @change="onChildrenChange"
    >
      <template #item="{ element: child, index: childIndex }">
        <CategoryReorderItem 
          :element="child"
          :index="childIndex"
          :view-mode="viewMode"
          :level="level + 1"
          :parent-index="displayIndex"
        />
      </template>
    </draggable>
  </div>
</template>

<script>
import draggable from 'vuedraggable'

export default {
  name: 'CategoryReorderItem',

  components: {
    draggable
  },

  props: {
    element: {
      type: Object,
      required: true
    },
    index: {
      type: Number,
      required: true
    },
    viewMode: {
      type: String,
      default: 'all'
    },
    level: {
      type: Number,
      default: 0
    },
    parentIndex: {
      type: String,
      default: ''
    }
  },

  data() {
    return {
      localChildren: []
    }
  },

  computed: {
    displayIndex() {
      if (this.parentIndex) {
        return `${this.parentIndex}.${this.index + 1}`
      }
      return `${this.index + 1}`
    }
  },

  watch: {
    'element.children': {
      handler(newVal) {
        this.localChildren = newVal || []
      },
      immediate: true,
      deep: true
    }
  },

  methods: {
    onChildrenChange() {
      if (this.element.children) {
        this.element.children.splice(0, this.element.children.length, ...this.localChildren)
      }
    }
  }
}
</script>

<style scoped>
.reorder-item {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  transition: all 0.3s;
  position: relative;
}

.reorder-item:hover {
  background: #f5f5f5;
  border-color: #1976d2;
}

.drag-handle {
  cursor: move;
  user-select: none;
}

.drag-handle:active {
  cursor: grabbing;
}

.children-container {
  min-height: 20px;
}

:deep(.sortable-ghost) {
  opacity: 0.4;
  background: #e3f2fd;
}

:deep(.sortable-drag) {
  opacity: 0.8;
  cursor: grabbing !important;
}
</style>