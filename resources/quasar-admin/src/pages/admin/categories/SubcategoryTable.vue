<template>
  <div :style="{ paddingLeft: (level * 20) + 'px' }">
    <q-list bordered separator>
      <q-item 
        v-for="category in categories" 
        :key="category.uuid"
        class="q-pa-sm"
      >
        <q-item-section>
          <div class="row items-center">
            <!-- Expand Button -->
            <q-btn
              v-if="category.children_count > 0"
              flat
              dense
              round
              size="sm"
              :icon="expanded[category.uuid] ? 'expand_more' : 'chevron_right'"
              @click="toggleExpand(category)"
            />
            <div v-else style="width: 36px;"></div>

            <!-- Name -->
            <div class="col-3">
              <strong>{{ category.name }}</strong>
            </div>

            <!-- Slug -->
            <div class="col-3 text-grey-7">
              {{ category.slug }}
            </div>

            <!-- Subcategory Count -->
            <div class="col-1 text-center">
              <q-badge 
                v-if="category.children_count > 0"
                color="blue-grey-6"
                class="cursor-pointer"
                @click="toggleExpand(category)"
              >
                {{ category.children_count }}
              </q-badge>
              <span v-else class="text-grey-6">—</span>
            </div>

            <!-- Featured -->
            <div class="col-1 text-center">
              <q-badge 
                :color="category.featured ? 'positive' : 'grey-5'" 
                :label="category.featured ? 'Yes' : 'No'"
              />
            </div>

            <!-- Status -->
            <div class="col-2 text-center">
              <q-toggle
                :model-value="category.status"
                color="positive"
                :disable="category.statusLoading"
                @update:model-value="$emit('change-status', category.uuid, $event)"
              />
            </div>

            <!-- Actions -->
            <div class="col-2 text-center q-gutter-x-sm">
              <q-btn
                flat
                dense
                round
                icon="edit"
                color="primary"
                size="sm"
                :to="`/admin/categories/${category.uuid}/edit`"
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
                @click="$emit('delete-category', category.uuid)"
              >
                <q-tooltip>Delete</q-tooltip>
              </q-btn>
            </div>
          </div>

          <!-- Nested Subcategories -->
          <div v-if="expanded[category.uuid]" class="q-mt-md">
            <div v-if="loadingChildren[category.uuid]" class="text-center q-pa-md">
              <q-spinner size="30px" color="primary" />
            </div>
            <SubcategoryTable
              v-else-if="children[category.uuid]"
              :categories="children[category.uuid]"
              :level="level + 1"
              @change-status="(uuid, status) => $emit('change-status', uuid, status)"
              @delete-category="(uuid) => $emit('delete-category', uuid)"
              @load-subcategories="(id, uuid) => $emit('load-subcategories', id, uuid)"
            />
          </div>
        </q-item-section>
      </q-item>
    </q-list>
  </div>
</template>

<script>
export default {
  name: 'SubcategoryTable',

  props: {
    categories: {
      type: Array,
      required: true
    },
    level: {
      type: Number,
      default: 0
    }
  },

  data() {
    return {
      expanded: {},
      children: {},
      loadingChildren: {}
    }
  },

  methods: {
    async toggleExpand(category) {
      const uuid = category.uuid
      
      // Use spread operator instead of direct mutation
      this.expanded = {
        ...this.expanded,
        [uuid]: !this.expanded[uuid]
      }

      if (this.expanded[uuid] && !this.children[uuid]) {
        this.loadingChildren = {
          ...this.loadingChildren,
          [uuid]: true
        }

        try {
          const res = await this.$axios.get('/admin/categories', {
            params: { parent_id: category.id }
          })
          
          // Use spread operator instead of $set
          this.children = {
            ...this.children,
            [uuid]: res.data
          }
        } catch (error) {
          console.error(error)
          this.$q.notify({
            type: 'negative',
            message: 'Failed to load subcategories'
          })
        } finally {
          this.loadingChildren = {
            ...this.loadingChildren,
            [uuid]: false
          }
        }
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