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
        @click="openReorderDialog"
      />
      <q-btn
        color="primary"
        label="Add Category"
        icon="add"
        unelevated
        to="/categories/create"
      />
    </div>

    <!-- Breadcrumbs Navigation -->
    <q-breadcrumbs v-if="breadcrumbs.length > 0" class="q-mb-md">
      <q-breadcrumbs-el 
        label="All Categories" 
        @click="navigateToRoot"
        class="cursor-pointer"
      />
      <q-breadcrumbs-el 
        v-for="(crumb, index) in breadcrumbs"
        :key="crumb.uuid"
        :label="crumb.name"
        @click="navigateToBreadcrumb(index)"
        class="cursor-pointer"
      />
    </q-breadcrumbs>

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
      :loading="loading"
      class="shadow-1"
    >
      <!-- Subcategory Count - Clickable -->
      <template #body-cell-subcategories="props">
        <q-td :props="props">
          <q-badge 
            v-if="props.row.children_count > 0"
            color="blue-grey-6"
            class="cursor-pointer"
            @click="viewSubcategories(props.row)"
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
            @update:model-value="changeStatus(props.row, $event)"
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
            :to="`/categories/${props.row.uuid}/edit`"
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
    </q-table>

    <!-- Reorder Modal Dialog -->
    <q-dialog v-model="showReorderDialog" maximized>
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">
            Reorder {{ currentLevelName }}
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pt-md" style="max-height: 70vh; overflow-y: auto;">
          <div v-if="reorderLoading" class="text-center q-pa-md">
            <q-spinner color="primary" size="40px" />
          </div>

          <div v-else-if="reorderCategories.length === 0" class="text-center q-pa-md text-grey-7">
            No categories to reorder at this level
          </div>

          <draggable
            v-else
            v-model="reorderCategories"
            item-key="uuid"
            handle=".drag-handle"
            @end="onReorderDragEnd"
            class="reorder-list"
          >
            <template #item="{ element, index }">
              <q-item class="reorder-item q-mb-sm">
                <q-item-section side class="drag-handle cursor-move">
                  <q-icon name="drag_indicator" color="grey-6" size="24px" />
                </q-item-section>

                <q-item-section>
                  <q-item-label>
                    <strong>{{ index + 1 }}.</strong> {{ element.name }}
                    <q-badge 
                      v-if="element.featured" 
                      color="positive" 
                      class="q-ml-sm"
                    >
                      Featured
                    </q-badge>
                    <q-badge 
                      v-if="element.children_count > 0" 
                      color="blue-grey-5" 
                      class="q-ml-sm"
                    >
                      {{ element.children_count }} sub
                    </q-badge>
                  </q-item-label>
                  
                  <q-item-label caption>
                    Slug: {{ element.slug }}
                  </q-item-label>
                </q-item-section>

                <q-item-section side>
                  <div class="text-caption text-grey-7">
                    Order: {{ element.sort_order }}
                  </div>
                </q-item-section>
              </q-item>
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
            <li>Categories at the top will appear first</li>
            <li>Only categories at the current level are shown for reordering</li>
            <li>Navigate to subcategories in the main table to reorder them</li>
          </ul>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import draggable from 'vuedraggable'
import axios from 'axios'

export default {
  name: 'CategoryList',

  components: {
    draggable
  },

  data() {
    return {
      categories: [],
      loading: false,
      search: '',
      statusFilter: null,
      featuredFilter: null,
      currentParentId: null,
      breadcrumbs: [],
      showReorderDialog: false,
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
        { name: 'name', label: 'Name', field: 'name', align: 'left', sortable: true },
        { name: 'slug', label: 'Slug', field: 'slug', align: 'left', sortable: true },
        { name: 'subcategories', label: 'Subcategories', align: 'center' },
        { name: 'featured', label: 'Featured', field: 'featured', align: 'center' },
        { name: 'status', label: 'Status', align: 'center' },
        { name: 'actions', label: 'Actions', align: 'center' }
      ]
    }
  },

  computed: {
    hasReorderChanges() {
      return JSON.stringify(this.reorderCategories) !== JSON.stringify(this.originalReorderCategories)
    },

    currentLevelName() {
      if (this.breadcrumbs.length > 0) {
        return `Subcategories of "${this.breadcrumbs[this.breadcrumbs.length - 1].name}"`
      }
      return 'Categories'
    }
  },

  mounted() {
    this.fetchCategories()
  },

  methods: {
    async fetchCategories() {
      this.loading = true
      try {
        const params = {
          search: this.search
        }

        // If we're viewing subcategories, filter by parent
        if (this.currentParentId) {
          params.parent_id = this.currentParentId
        } else {
          // Otherwise show only main categories
          params.main_only = true
        }

        if (this.statusFilter !== null) {
          params.status = this.statusFilter
        }

        if (this.featuredFilter !== null) {
          params.featured = this.featuredFilter
        }

        const res = await axios.get('/admin/categories', { params })
        this.categories = res.data
      } catch (error) {
        console.error(error)
        this.$q.notify({
          type: 'negative',
          message: 'Failed to fetch categories'
        })
      } finally {
        this.loading = false
      }
    },

    viewSubcategories(category) {
      // Add to breadcrumbs
      this.breadcrumbs.push({
        uuid: category.uuid,
        id: category.id,
        name: category.name
      })

      // Set current parent and fetch children
      this.currentParentId = category.id
      this.fetchCategories()
    },

    navigateToRoot() {
      this.breadcrumbs = []
      this.currentParentId = null
      this.fetchCategories()
    },

    navigateToBreadcrumb(index) {
      // Remove breadcrumbs after the clicked one
      const clickedCrumb = this.breadcrumbs[index]
      this.breadcrumbs = this.breadcrumbs.slice(0, index + 1)
      
      // Set parent and fetch
      this.currentParentId = clickedCrumb.id
      this.fetchCategories()
    },

    async changeStatus(category, newStatus) {
      const oldStatus = category.status
      category.status = newStatus
      category.statusLoading = true

      try {
        await axios.patch(`/admin/categories/${category.uuid}/status`, {
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
        cancel: { flat: true, label: 'Cancel' },
        ok: { flat: true, label: 'Delete', color: 'negative' },
        persistent: true
      }).onOk(async () => {
        try {
          await axios.delete(`/admin/categories/${uuid}`)
          this.fetchCategories()
          this.$q.notify({ 
            type: 'positive', 
            message: 'Category deleted successfully',
            position: 'top'
          })
        } catch (error) {
          console.error(error)
          const message = error.response?.data?.message || 'Failed to delete category'
          this.$q.notify({ type: 'negative', message, position: 'top' })
        }
      })
    },

    openReorderDialog() {
      this.showReorderDialog = true
      this.fetchReorderCategories()
    },

    async fetchReorderCategories() {
      this.reorderLoading = true
      try {
        const params = {}
        
        // Fetch categories at current level only
        if (this.currentParentId) {
          params.parent_id = this.currentParentId
        } else {
          params.main_only = true
        }

        const res = await axios.get('/admin/categories', { params })
        
        // Sort by sort_order
        this.reorderCategories = [...res.data].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
        this.originalReorderCategories = JSON.parse(JSON.stringify(this.reorderCategories))
      } catch (error) {
        console.error(error)
        this.$q.notify({ type: 'negative', message: 'Failed to fetch categories' })
      } finally {
        this.reorderLoading = false
      }
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
        const items = this.reorderCategories.map((cat, index) => ({
          uuid: cat.uuid,
          sort_order: index + 1
        }))

        await axios.post('/admin/categories/reorder', { items })

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
        this.$q.notify({ type: 'negative', message: 'Failed to save order', position: 'top' })
      } finally {
        this.reorderSaving = false
      }
    }
  }
}
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.reorder-list {
  min-height: 50px;
}

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

:deep(.sortable-ghost) {
  opacity: 0.4;
  background: #e3f2fd;
}

:deep(.sortable-drag) {
  opacity: 0.8;
  cursor: grabbing !important;
}
</style>