<template>
  <div :style="{ marginLeft: (level * 30) + 'px' }">
    <q-item class="reorder-item q-mb-sm">
      <q-item-section side class="drag-handle cursor-move">
        <q-icon name="drag_indicator" color="grey-6" size="24px" />
      </q-item-section>

      <q-item-section>
        <q-item-label>
          <span class="text-grey-7" v-if="level > 0">└─ </span>
          <strong>{{ displayIndex }}.</strong> {{ element.name }}

          <q-badge v-if="element.featured" color="positive" class="q-ml-sm">
            Featured
          </q-badge>

          <q-badge v-if="localChildren.length > 0" color="blue-grey-5" class="q-ml-sm">
            {{ localChildren.length }} sub
          </q-badge>
        </q-item-label>

        <q-item-label caption>
          Slug: {{ element.slug }}
        </q-item-label>
      </q-item-section>

      <q-item-section side>
        <div class="text-caption text-grey-7">
          {{ viewMode === 'featured' ? 'Featured Order' : 'Sort Order' }}:
          {{ viewMode === 'featured' ? element.featured_order : element.sort_order }}
        </div>
      </q-item-section>
    </q-item>

    <!-- Recursive draggable children -->
    <draggable
      v-if="localChildren.length > 0"
      v-model="localChildren"
      item-key="uuid"
      handle=".drag-handle"
      :group="{ name: 'categories' }"
      @end="emitChildrenUpdate"
      class="children-list"
      tag="div"
    >
      <template #item="{ element: child, index: childIndex }">
        <CategoryReorderItem
          :element="child"
          :index="childIndex"
          :view-mode="viewMode"
          :level="level + 1"
          :parent-index="displayIndex"
          @update-children="forwardChildrenUpdate"
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
      localChildren: this.element.children ? [...this.element.children] : []
    }
  },

  watch: {
    'element.children': {
      deep: true,
      handler(val) {
        this.localChildren = val ? [...val] : []
      }
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

  methods: {
    emitChildrenUpdate() {
      this.$emit('update-children', {
        uuid: this.element.uuid,
        children: this.localChildren
      })
    },

    forwardChildrenUpdate(payload) {
      this.$emit('update-children', payload)
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

.children-list {
  min-height: 10px;
}

.sortable-ghost {
  opacity: 0.4;
  background: #e3f2fd;
}

.sortable-drag {
  opacity: 0.8;
  cursor: grabbing !important;
}
</style>