<template>
  <div>
    <div v-if="loading" class="text-center q-pa-xl">
      <q-spinner color="primary" size="50px" />
    </div>

    <div v-else-if="categories.length === 0" class="text-center q-pa-xl text-grey-7">
      No categories found
    </div>

    <q-list v-else separator>
      <CategoryTreeItem
        v-for="category in categories"
        :key="category.uuid"
        :category="category"
        :level="0"
        @change-status="handleStatusChange"
        @delete-category="$emit('delete-category', $event)"
      />
    </q-list>
  </div>
</template>

<script>
import CategoryTreeItem from './CategoryTreeItem.vue'

export default {
  name: 'CategoryTreeView',

  components: {
    CategoryTreeItem
  },

  props: {
    categories: {
      type: Array,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    }
  },

  methods: {
    async handleStatusChange(uuid, newStatus) {
      try {
        await this.$emit('change-status', uuid, newStatus)
      } catch (error) {
        // Status change failed, will be handled by parent
      }
    }
  }
}
</script>