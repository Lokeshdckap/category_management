<template>
  <q-page class="q-pa-md">
    <div class="row items-center q-mb-md">
      <div class="text-h5 text-weight-medium">Categories</div>
      <q-space />
      <q-btn
        outline
        color="primary"
        label="Reorder"
        icon="swap_vert"
        class="q-mr-sm"
        @click="showReorderDialog = true"
      />
      <q-btn
        color="primary"
        label="Add Category"
        icon="add"
        unelevated
        to="/admin/categories/create"
      />
    </div>

    <!-- Filters -->
    <div class="row q-gutter-md q-mb-md">
      <q-input
        outlined
        dense
        debounce="300"
        v-model="search"
        placeholder="Search categories..."
        style="max-width: 400px"
        @update:model-value="fetchCategories"
      >
        <template v-slot:prepend>
          <q-icon name="search" />
        </template>
        <template v-slot:append v-if="search">
          <q-icon 
            name="close" 
            @click="search = ''; fetchCategories()" 
            class="cursor-pointer" 
          />
        </template>
      </q-input>

      <q-select
        outlined
        dense
        v-model="statusFilter"
        :options="statusOptions"
        emit-value
        map-options
        label="Status"
        style="min-width: 150px"
        @update:model-value="fetchCategories"
      />

      <q-select
        outlined
        dense
        v-model="featuredFilter"
        :options="featuredOptions"
        emit-value
        map-options
        label="Featured"
        style="min-width: 150px"
        @update:model-value="fetchCategories"
      />
    </div>

    <!-- Table -->
    <q-table
      :rows="categories"
      :columns="columns"
      row-key="uuid"
      flat
      bordered
      :rows-per-page-options="[10, 25, 50]"
      class="shadow-1"
    >
      <!-- Name with Expand Icon -->
      <template #body-cell-name="props">
        <q-td :props="props">
          <div class="row items-center no-wrap">
            <q-btn
              v-if="props.row.children_count > 0"
              flat
              dense
              round
              size="sm"
              :icon="props.expand ? 'expand_more' : 'chevron_right'"
              @click="props.expand = !props.expand"
            />
            <span :class="{ 'q-ml-sm': props.row.children_count > 0 }">
              {{ props.row.name }}
            </span>
          </div>
        </q-td>
      </template>

      <!-- Subcategory Count - Clickable -->
      <template #body-cell-subcategories="props">
        <q-td :props="props">
          <q-badge 
            v-if="props.row.children_count > 0"
            color="blue-grey-6"
            class="cursor-pointer"
            @click="props.expand = !props.expand"
          >
            {{ props.row.children_count }}
          </q-badge>
          <span v-else class="text-grey-6">—</span>
        </q-td>
      </template>

      <!-- Featured Badge -->
      <template #body-cell-featured="props">
        <q-td :props="props">
          <q-badge 
            :color="props.row.featured ? 'positive' : 'grey-5'" 
            :label="props.row.featured ? 'Yes' : 'No'"
          />
        </q-td>
      </template>

      <!-- Status Toggle -->
      <template #body-cell-status="props">
        <q-td :props="props">
          <q-toggle
            :model-value="props.row.status"
            color="positive"
            :disable="props.row.statusLoading"
            @update:model-value="changeStatus(props.row.uuid, $event)"
          />
        </q-td>
      </template>

      <!-- Actions -->
      <template #body-cell-actions="props">
        <q-td :props="props" class="q-gutter-x-sm">
          <q-btn
            flat
            dense
            round
            icon="edit"
            color="primary"
            size="sm"
            :to="`/admin/categories/${props.row.uuid}/edit`"
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
            @click="deleteCategory(props.row.uuid)"
          >
            <q-tooltip>Delete</q-tooltip>
          </q-btn>
        </q-td>
      </template>

      <!-- Expanded Row - Show Subcategories -->
      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td 
            v-for="col in props.cols" 
            :key="col.name" 
            :props="props"
          >
            <!-- Name Column -->
            <div v-if="col.name === 'name'" class="row items-center no-wrap">
              <q-btn
                v-if="props.row.children_count > 0"
                flat
                dense
                round
                size="sm"
                :icon="props.expand ? 'expand_more' : 'chevron_right'"
                @click="toggleExpand(props)"
              />
              <span :class="{ 'q-ml-sm': props.row.children_count > 0 }">
                {{ props.row.name }}
              </span>
            </div>

            <!-- Subcategories Column -->
            <div v-else-if="col.name === 'subcategories'">
              <q-badge 
                v-if="props.row.children_count > 0"
                color="blue-grey-6"
                class="cursor-pointer"
                @click="toggleExpand(props)"
              >
                {{ props.row.children_count }}
              </q-badge>
              <span v-else class="text-grey-6">—</span>
            </div>

            <!-- Featured Column -->
            <div v-else-if="col.name === 'featured'">
              <q-badge 
                :color="props.row.featured ? 'positive' : 'grey-5'" 
                :label="props.row.featured ? 'Yes' : 'No'"
              />
            </div>

            <!-- Status Column -->
            <div v-else-if="col.name === 'status'">
              <q-toggle
                :model-value="props.row.status"
                color="positive"
                :disable="props.row.statusLoading"
                @update:model-value="changeStatus(props.row.uuid, $event)"
              />
            </div>

            <!-- Actions Column -->
            <div v-else-if="col.name === 'actions'" class="q-gutter-x-sm">
              <q-btn
                flat
                dense
                round
                icon="edit"
                color="primary"
                size="sm"
                :to="`/admin/categories/${props.row.uuid}/edit`"
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
                @click="deleteCategory(props.row.uuid)"
              >
                <q-tooltip>Delete</q-tooltip>
              </q-btn>
            </div>

            <!-- Other Columns -->
            <div v-else>{{ col.value }}</div>
          </q-td>
        </q-tr>

        <!-- Expanded Subcategories Row -->
        <q-tr v-if="props.expand" :props="props">
          <q-td colspan="100%">
            <div class="q-pa-md bg-grey-1">
              <div class="text-subtitle2 q-mb-md">
                Subcategories of "{{ props.row.name }}"
                <q-spinner v-if="loadingSubcategories[props.row.uuid]" size="20px" class="q-ml-sm" />
              </div>

              <!-- Recursive Subcategory Table -->
              <SubcategoryTable 
                v-if="subcategories[props.row.uuid]"
                :categories="subcategories[props.row.uuid]"
                :level="1"
                @change-status="changeStatus"
                @delete-category="deleteCategory"
                @load-subcategories="loadSubcategories"
              />
            </div>
          </q-td>
        </q-tr>
      </template>
    </q-table>
     <q-dialog v-model="showReorderDialog" maximized>
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Reorder Categories</div>
          <q-space />
          <q-btn-toggle
            v-model="reorderViewMode"
            toggle-color="primary"
            :options="[
              { label: 'All Categories', value: 'all' },
              { label: 'Featured Only', value: 'featured' }
            ]"
            @update:model-value="fetchReorderCategories"
          />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pt-md" style="max-height: 70vh; overflow-y: auto;">
          <div v-if="reorderLoading" class="text-center q-pa-md">
            <q-spinner color="primary" size="40px" />
          </div>

          <div v-else-if="reorderCategories.length === 0" class="text-center q-pa-md text-grey-7">
            No categories to reorder
          </div>

          <draggable
            v-else
            v-model="reorderCategories"
            item-key="uuid"
            handle=".drag-handle"
            @end="onReorderDragEnd"
            :group="{ name: 'categories' }"
            class="reorder-list"
          >
            <template #item="{ element, index }">
              <CategoryReorderItem 
                :element="element"
                :index="index"
                :view-mode="reorderViewMode"
                :level="0"
              />
            </template>
          </draggable>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-px-md q-py-md">
          <q-btn 
            flat 
            label="Cancel" 
            color="grey-7"
            v-close-popup
          />
          <q-btn 
            unelevated
            color="primary" 
            label="Save Order"  
            :loading="reorderSaving" 
            :disable="!hasReorderChanges"
            @click="saveReorderOrder" 
          />
        </q-card-actions>

        <!-- Instructions -->
        <q-card-section class="bg-grey-2">
          <div class="text-subtitle2 q-mb-sm">
            <q-icon name="info" color="primary" /> How to reorder
          </div>
          <ul class="q-pl-md text-body2 q-mb-none">
            <li>Drag categories using the handle icon (☰) on the left</li>
            <li>Categories at the top will appear first on your site</li>
            <li>Hierarchical structure shows parent-child relationships</li>
            <li>Use "All Categories" to set the general display order</li>
            <li>Use "Featured Only" to set the order for featured categories</li>
          </ul>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script>
import SubcategoryTable from './SubcategoryTable.vue'
import CategoryReorderItem from './CategoryReorderItem.vue'
import draggable from 'vuedraggable'


export default {
  name: 'CategoryList',

  components: {
    SubcategoryTable,
    CategoryReorderItem,
    draggable
  },

  data() {
    return {
      categories: [],
      subcategories: {},
      loadingSubcategories: {},
      search: '',
      statusFilter: null,
      featuredFilter: null,
      showReorderDialog: false,
      reorderViewMode: 'all',
      reorderCategories: [],
      originalReorderCategories: [],
      reorderLoading: false,
      reorderSaving: false,
      
      statusOptions: [
        { label: 'All', value: null },
        { label: 'Active', value: true },
        { label: 'Inactive', value: false }
      ],
      
      featuredOptions: [
        { label: 'All', value: null },
        { label: 'Featured', value: true },
        { label: 'Not Featured', value: false }
      ],
      
      columns: [
        { 
          name: 'name', 
          label: 'Name', 
          field: 'name', 
          align: 'left',
          sortable: true 
        },
        { 
          name: 'slug', 
          label: 'Slug', 
          field: 'slug',
          align: 'left',
          sortable: true 
        },
        { 
          name: 'subcategories', 
          label: 'Subcategories', 
          align: 'center'
        },
        { 
          name: 'featured', 
          label: 'Featured', 
          field: 'featured',
          align: 'center' 
        },
        { 
          name: 'status', 
          label: 'Status',
          align: 'center' 
        },
        { 
          name: 'actions', 
          label: 'Actions',
          align: 'center' 
        }
      ]
    }
  },

  computed: {
    hasReorderChanges() {
      if (this.reorderCategories.length !== this.originalReorderCategories.length) {
        return true
      }

      return this.reorderCategories.some((cat, index) => {
        const original = this.originalReorderCategories[index]
        return cat.uuid !== original.uuid
      })
    }
  },

   watch: {
    showReorderDialog(val) {
      if (val) {
        this.fetchReorderCategories()
      }
    }
  },

  mounted() {
    this.fetchCategories()
  },

  methods: {
    async fetchCategories() {
      try {
        const params = {
          search: this.search,
          main_only: true
        }

        if (this.statusFilter !== null) {
          params.status = this.statusFilter
        }

        if (this.featuredFilter !== null) {
          params.featured = this.featuredFilter
        }

        const res = await this.$axios.get('/admin/categories', { params })
        this.categories = res.data
      } catch (error) {
        console.error(error)
        this.$q.notify({ 
          type: 'negative', 
          message: 'Failed to fetch categories' 
        })
      }
    },

    async toggleExpand(props) {
      props.expand = !props.expand
      
      if (props.expand && !this.subcategories[props.row.uuid]) {
        await this.loadSubcategories(props.row.id, props.row.uuid)
      }
    },

    async loadSubcategories(parentId, parentUuid) {
      if (this.subcategories[parentUuid]) return

      this.loadingSubcategories = {
        ...this.loadingSubcategories,
        [parentUuid]: true
      }

      try {
        const res = await this.$axios.get('/admin/categories', {
          params: { parent_id: parentId }
        })
        
        // Use spread operator instead of $set
        this.subcategories = {
          ...this.subcategories,
          [parentUuid]: res.data
        }
      } catch (error) {
        console.error(error)
        this.$q.notify({
          type: 'negative',
          message: 'Failed to load subcategories'
        })
      } finally {
        this.loadingSubcategories = {
          ...this.loadingSubcategories,
          [parentUuid]: false
        }
      }
    },

    async changeStatus(uuid, newStatus) {
      // Find in main categories
      let category = this.categories.find(cat => cat.uuid === uuid)
      
      // Search in all subcategories
      if (!category) {
        for (const key in this.subcategories) {
          const findInNested = (items) => {
            for (const item of items) {
              if (item.uuid === uuid) return item
              if (this.subcategories[item.uuid]) {
                const found = findInNested(this.subcategories[item.uuid])
                if (found) return found
              }
            }
            return null
          }
          category = findInNested(this.subcategories[key])
          if (category) break
        }
      }

      if (!category) return

      const oldStatus = category.status
      
      category.status = newStatus
      category.statusLoading = true

      try {
        await this.$axios.patch(`/admin/categories/${uuid}/status`, {
          status: newStatus
        })
        
        this.$q.notify({ 
          type: 'positive', 
          message: 'Status updated successfully',
          position: 'top'
        })
      } catch (error) {
        console.error(error)
        category.status = oldStatus
        
        this.$q.notify({ 
          type: 'negative', 
          message: 'Failed to update status' 
        })
      } finally {
        category.statusLoading = false
      }
    },

    deleteCategory(uuid) {
      this.$q.dialog({
        title: 'Confirm Delete',
        message: 'Are you sure you want to delete this category? This action cannot be undone.',
        cancel: {
          flat: true,
          label: 'Cancel'
        },
        ok: {
          flat: true,
          label: 'Delete',
          color: 'negative'
        },
        persistent: true
      }).onOk(async () => {
        try {
          await this.$axios.delete(`/admin/categories/${uuid}`)
          
          // Clear subcategories cache
          this.subcategories = {}
          
          this.fetchCategories()
          this.$q.notify({ 
            type: 'positive', 
            message: 'Category deleted successfully',
            position: 'top'
          })
        } catch (error) {
          console.error(error)
          
          const message = error.response?.data?.message || 'Failed to delete category'
          
          this.$q.notify({ 
            type: 'negative', 
            message,
            position: 'top'
          })
        }
      })
    },

   async fetchReorderCategories() {
      this.reorderLoading = true
      try {
        const params = {}
        
        if (this.reorderViewMode === 'featured') {
          params.featured = true
        }

        const res = await this.$axios.get('/admin/categories', { params })
        
        // Sort by the appropriate order field
        const sortField = this.reorderViewMode === 'featured' ? 'featured_order' : 'sort_order'
        const allCategories = res.data.sort((a, b) => a[sortField] - b[sortField])
        
        console.log('All categories:', allCategories)
        
        // Build tree structure
        this.reorderCategories = this.buildCategoryTree(allCategories)
        
        console.log('Tree structure:', this.reorderCategories)
        
        this.originalReorderCategories = JSON.parse(JSON.stringify(this.reorderCategories))
      } catch (error) {
        console.error(error)
        this.$q.notify({ 
          type: 'negative', 
          message: 'Failed to fetch categories' 
        })
      } finally {
        this.reorderLoading = false
      }
    },

    buildCategoryTree(categories) {
      // Create a map of categories by ID
      const categoryMap = new Map()
      
      // First pass: create map entries
      categories.forEach(cat => {
        categoryMap.set(cat.id, {
          ...cat,
          children: []
        })
      })

      const tree = []

      // Second pass: build hierarchy
      categories.forEach(cat => {
        const categoryNode = categoryMap.get(cat.id)
        
        if (cat.parent_id && categoryMap.has(cat.parent_id)) {
          // Has parent - add to parent's children
          const parent = categoryMap.get(cat.parent_id)
          parent.children.push(categoryNode)
        } else {
          // No parent - add to root
          tree.push(categoryNode)
        }
      })

      console.log('Built tree:', tree)
      return tree
    },

    onReorderDragEnd() {
      this.$q.notify({
        type: 'info',
        message: 'Order changed. Click "Save Order" to apply changes.',
        position: 'top',
        timeout: 2000
      })
    },

    async saveReorderOrder() {
      this.reorderSaving = true

      try {
        // Flatten the tree to get sequential order
        const flattenedCategories = this.flattenCategoryTree(this.reorderCategories)
        
        console.log('Flattened for saving:', flattenedCategories)
        
        const items = flattenedCategories.map((cat, index) => ({
          uuid: cat.uuid,
          [this.reorderViewMode === 'featured' ? 'featured_order' : 'sort_order']: index + 1
        }))

        const endpoint = this.reorderViewMode === 'featured' 
          ? '/admin/categories/reorder-featured' 
          : '/admin/categories/reorder'

        await this.$axios.post(endpoint, { items })

        this.$q.notify({ 
          type: 'positive', 
          message: 'Order saved successfully',
          position: 'top'
        })

        this.originalReorderCategories = JSON.parse(JSON.stringify(this.reorderCategories))
        this.showReorderDialog = false
        this.fetchCategories()

      } catch (error) {
        console.error(error)
        this.$q.notify({ 
          type: 'negative', 
          message: 'Failed to save order',
          position: 'top'
        })
      } finally {
        this.reorderSaving = false
      }
    },

    flattenCategoryTree(categories, result = []) {
      categories.forEach(cat => {
        result.push(cat)
        if (cat.children && cat.children.length > 0) {
          this.flattenCategoryTree(cat.children, result)
        }
      })
      return result
    }
  }
}
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>