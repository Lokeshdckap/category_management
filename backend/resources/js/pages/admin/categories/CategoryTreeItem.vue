<template>
  <div>
    <!-- Category Row -->
    <q-item
      clickable
      :class="{ 'bg-grey-1': level > 0 }"
      :style="{ paddingLeft: (level * 30 + 16) + 'px' }"
    >
      <!-- Expand Button -->
      <q-item-section side style="min-width: 40px">
        <q-btn
          v-if="category.children_count > 0"
          flat
          dense
          round
          size="sm"
          :icon="expanded ? 'expand_more' : 'chevron_right'"
          @click.stop="toggleExpand"
        />
      </q-item-section>

      <!-- Name -->
      <q-item-section>
        <q-item-label>
          <span class="text-grey-7" v-if="level > 0">└─</span>
          <strong class="q-ml-xs">{{ category.name }}</strong>
          <q-badge 
            v-if="category.featured" 
            color="positive" 
            class="q-ml-sm"
          >
            Featured
          </q-badge>
        </q-item-label>
        <q-item-label caption>
          {{ category.slug }}
        </q-item-label>
      </q-item-section>

      <!-- Subcategory Count -->
      <q-item-section side style="min-width: 120px" class="text-center">
        <q-badge 
          v-if="category.children_count > 0"
          color="blue-grey-6"
          class="cursor-pointer"
          @click.stop="toggleExpand"
        >
          {{ category.children_count }} sub
        </q-badge>
        <span v-else class="text-grey-6">—</span>
      </q-item-section>

      <!-- Featured Badge -->
      <q-item-section side style="min-width: 100px" class="text-center">
        <q-badge 
          :color="category.featured ? 'positive' : 'grey-5'" 
          :label="category.featured ? 'Yes' : 'No'"
        />
      </q-item-section>

      <!-- Status Toggle -->
      <q-item-section side style="min-width: 100px" class="text-center">
        <q-toggle
          :model-value="category.status"
          color="positive"
          :disable="statusLoading"
          @update:model-value="handleStatusChange"
          @click.stop
        />
      </q-item-section>

      <!-- Actions -->
      <q-item-section side style="min-width: 120px">
        <div class="q-gutter-x-sm">
          <q-btn
            flat
            dense
            round
            icon="edit"
            color="primary"
            size="sm"
            :to="`/admin/categories/${category.uuid}/edit`"
            @click.stop
          >
            <q-tooltip>Edit</q-tooltip>
          </q-btn>
          <q-btn
            flat
            dense
            round
            icon="delete"
            color="negative"
            size="sm"
            @click.stop="$emit('delete-category', category.uuid)"
          >
            <q-tooltip>Delete</q-tooltip>
          </q-btn>
        </div>
      </q-item-section>
    </q-item>

    <!-- Children (Expanded) -->
    <div v-if="expanded">
      <div v-if="loadingChildren" class="text-center q-pa-md">
        <q-spinner color="primary" size="30px" />
      </div>

      <CategoryTreeItem
        v-else
        v-for="child in children"
        :key="child.uuid"
        :category="child"
        :level="level + 1"
        @change-status="$emit('change-status', $event, $event)"
        @delete-category="$emit('delete-category', $event)"
      />
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CategoryTreeItem',

  props: {
    category: {
      type: Object,
      required: true
    },
    level: {
      type: Number,
      default: 0
    }
  },

  data() {
    return {
      expanded: false,
      children: [],
      loadingChildren: false,
      statusLoading: false
    }
  },

  methods: {
    async toggleExpand() {
      if (this.category.children_count === 0) return

      this.expanded = !this.expanded

      if (this.expanded && this.children.length === 0) {
        await this.loadChildren()
      }
    },

    async loadChildren() {
      this.loadingChildren = true
      try {
        const res = await axios.get('/admin/categories', {
          params: { parent_id: this.category.id }
        })
        this.children = res.data
      } catch (error) {
        console.error(error)
        this.$q.notify({
          type: 'negative',
          message: 'Failed to load subcategories'
        })
      } finally {
        this.loadingChildren = false
      }
    },

    async handleStatusChange(newStatus) {
      this.statusLoading = true
      const oldStatus = this.category.status

      this.category.status = newStatus

      try {
        await this.$emit('change-status', this.category.uuid, newStatus)
      } catch (error) {
        this.category.status = oldStatus
      } finally {
        this.statusLoading = false
      }
    }
  }
}
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>