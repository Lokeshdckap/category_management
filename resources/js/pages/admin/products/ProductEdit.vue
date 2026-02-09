<template>
  <q-page class="q-pa-md">
    <div class="q-pa-md" style="max-width: 1400px; margin: 0 auto">
      <!-- Header -->
      <div class="row items-center justify-between q-mb-lg">
        <div>
          <div class="text-h4 text-weight-bold text-grey-9">
            Edit Product
          </div>
          <div class="text-subtitle2 text-grey-6 q-mt-xs">
            Update product information
          </div>
        </div>
        <div class="row q-gutter-sm">
          <q-btn outline color="grey-7" label="Cancel" icon="close" @click="handleCancel" />
          <q-btn 
            unelevated 
            color="primary" 
            label="Update Product" 
            icon="save" 
            @click="handleUpdate"
            :loading="saving"
          />
        </div>
      </div>

      <!-- Loading State -->
      <q-inner-loading :showing="loading">
        <q-spinner-gears size="50px" color="primary" />
      </q-inner-loading>

      <!-- Product Form (reuse same structure as ProductCreate) -->
      <q-card flat bordered v-if="!loading">
        <q-tabs
          v-model="tab"
          dense
          class="text-grey-7"
          active-color="primary"
          indicator-color="primary"
          align="left"
          narrow-indicator
        >
          <q-tab name="basic" icon="info" label="Basic Info" />
          <q-tab name="categories" icon="category" label="Categories" />
          <q-tab name="compatible" icon="widgets" label="Compatible Products" />
          <q-tab name="images" icon="image" label="Images" />
          <q-tab name="seo" icon="search" label="SEO" />
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="tab" animated>
          <!-- Basic Info Tab -->
          <q-tab-panel name="basic">
            <div class="text-h6 q-mb-md">Basic Information</div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-input
                  v-model="product.name"
                  label="Product Name *"
                  outlined
                  dense
                  :rules="[val => !!val || 'Product name is required']"
                  @blur="autoGenerateSlug"
                >
                  <template v-slot:prepend>
                    <q-icon name="title" />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-md-6">
                <q-input
                  v-model="product.sku"
                  label="SKU *"
                  outlined
                  dense
                  :rules="[val => !!val || 'SKU is required']"
                >
                  <template v-slot:prepend>
                    <q-icon name="qr_code" />
                  </template>
                </q-input>
              </div>

              <div class="col-12">
                <q-input
                  v-model="product.short_description"
                  label="Short Description"
                  outlined
                  dense
                  type="textarea"
                  rows="3"
                  counter
                  maxlength="500"
                  hint="Brief product summary (max 500 characters)"
                >
                  <template v-slot:prepend>
                    <q-icon name="short_text" />
                  </template>
                </q-input>
              </div>

              <div class="col-12">
                <div class="text-subtitle2 q-mb-sm text-grey-8">
                  <q-icon name="description" class="q-mr-xs" />
                  Description
                </div>
                <q-card flat bordered>
                  <q-bar class="bg-grey-2">
                    <q-btn flat dense size="sm" icon="format_bold" @click="formatText('bold')">
                      <q-tooltip>Bold</q-tooltip>
                    </q-btn>
                    <q-btn flat dense size="sm" icon="format_italic" @click="formatText('italic')">
                      <q-tooltip>Italic</q-tooltip>
                    </q-btn>
                    <q-btn flat dense size="sm" icon="format_underlined" @click="formatText('underline')">
                      <q-tooltip>Underline</q-tooltip>
                    </q-btn>
                    <q-separator vertical inset />
                    <q-btn flat dense size="sm" icon="format_list_bulleted" @click="formatText('insertUnorderedList')">
                      <q-tooltip>Bullet List</q-tooltip>
                    </q-btn>
                    <q-btn flat dense size="sm" icon="format_list_numbered" @click="formatText('insertOrderedList')">
                      <q-tooltip>Numbered List</q-tooltip>
                    </q-btn>
                  </q-bar>
                  <q-separator />
                  <div
                    ref="editor"
                    contenteditable="true"
                    class="q-pa-md"
                    style="min-height: 300px; outline: none"
                    @input="updateDescription"
                    v-html="product.description"
                  ></div>
                </q-card>
              </div>
            </div>
          </q-tab-panel>

          <!-- Categories Tab (same as ProductCreate) -->
          <q-tab-panel name="categories">
            <div class="text-h6 q-mb-md">Categories</div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <div class="text-subtitle2 q-mb-sm text-grey-8">
                  <q-icon name="account_tree" class="q-mr-xs" />
                  Category Tree
                </div>
                <q-card flat bordered>
                  <q-tree
                    :nodes="categoryTree"
                    node-key="id"
                    tick-strategy="leaf"
                    v-model:ticked="product.categories"
                    v-model:expanded="expandedCategories"
                    default-expand-all
                    class="q-pa-md"
                  >
                    <template v-slot:default-header="prop">
                      <div class="row items-center">
                        <q-icon
                          :name="prop.node.icon || 'folder'"
                          size="20px"
                          class="q-mr-sm"
                          :color="prop.node.color || 'grey-6'"
                        />
                        <div>{{ prop.node.label }}</div>
                      </div>
                    </template>
                  </q-tree>
                </q-card>
              </div>

              <div class="col-12 col-md-6">
                <div class="text-subtitle2 q-mb-sm text-grey-8">
                  <q-icon name="star" class="q-mr-xs" />
                  Default Category *
                </div>
                <q-select
                  v-model="product.default_category_id"
                  :options="selectedCategoryOptions"
                  outlined
                  dense
                  label="Select Default Category"
                  hint="This will be the primary category for the product"
                  :disable="product.categories.length === 0"
                  emit-value
                  map-options
                >
                  <template v-slot:prepend>
                    <q-icon name="label" />
                  </template>
                </q-select>

                <div class="q-mt-md">
                  <div class="text-subtitle2 q-mb-sm text-grey-8">Selected Categories</div>
                  <q-card flat bordered v-if="product.categories.length > 0">
                    <q-list separator>
                      <q-item v-for="catId in product.categories" :key="catId">
                        <q-item-section avatar>
                          <q-icon name="label" color="primary" />
                        </q-item-section>
                        <q-item-section>
                          <q-item-label>{{ getCategoryLabel(catId) }}</q-item-label>
                        </q-item-section>
                        <q-item-section side>
                          <q-badge v-if="product.default_category_id === catId" color="primary" label="Default" />
                        </q-item-section>
                      </q-item>
                    </q-list>
                  </q-card>
                  <q-banner v-else class="bg-grey-2 text-grey-7">
                    <template v-slot:avatar>
                      <q-icon name="info" />
                    </template>
                    No categories selected
                  </q-banner>
                </div>
              </div>
            </div>
          </q-tab-panel>

          <!-- Compatible Products Tab (same logic) -->
          <q-tab-panel name="compatible">
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6">Compatible Products</div>
              <q-btn
                color="primary"
                icon="add"
                label="Add Products"
                @click="openProductDialog"
                unelevated
              />
            </div>

            <q-card flat bordered v-if="product.compatible_products.length > 0">
              <q-table
                :rows="compatibleProductsDetails"
                :columns="compatibleColumns"
                row-key="id"
                flat
                :pagination="{ rowsPerPage: 10 }"
              >
                <template v-slot:body-cell-name="props">
                  <q-td :props="props">
                    {{ props.row.name }}
                  </q-td>
                </template>

                <template v-slot:body-cell-sku="props">
                  <q-td :props="props">
                    {{ props.row.sku }}
                  </q-td>
                </template>

                <template v-slot:body-cell-actions="props">
                  <q-td :props="props">
                    <q-btn
                      flat
                      dense
                      round
                      icon="delete"
                      color="negative"
                      @click="removeCompatibleProduct(props.row.id)"
                    >
                      <q-tooltip>Remove</q-tooltip>
                    </q-btn>
                  </q-td>
                </template>
              </q-table>
            </q-card>

            <q-banner v-else class="bg-grey-2 text-grey-7">
              <template v-slot:avatar>
                <q-icon name="widgets" />
              </template>
              No compatible products added yet.
            </q-banner>
          </q-tab-panel>

          <!-- Images Tab -->
          <q-tab-panel name="images">
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6">Product Images</div>
              <q-btn
                color="primary"
                icon="add_photo_alternate"
                label="Upload New Images"
                @click="triggerFileUpload"
                unelevated
              />
            </div>

            <input
              type="file"
              ref="fileInput"
              @change="handleFileUpload"
              accept="image/*"
              multiple
              style="display: none"
            />

            <q-card flat bordered v-if="allImages.length > 0">
              <q-table
                :rows="allImages"
                :columns="imageColumns"
                row-key="id"
                flat
                :pagination="{ rowsPerPage: 5 }"
              >
                <template v-slot:body-cell-preview="props">
                  <q-td :props="props">
                    <q-img
                      :src="props.row.preview || props.row.url"
                      style="width: 80px; height: 80px"
                      class="rounded-borders"
                    >
                      <template v-slot:error>
                        <div class="absolute-full flex flex-center bg-grey-3">
                          <q-icon name="broken_image" size="40px" color="grey-5" />
                        </div>
                      </template>
                    </q-img>
                  </q-td>
                </template>

                <template v-slot:body-cell-alt="props">
                  <q-td :props="props">
                    <q-input v-model="props.row.alt" dense borderless placeholder="Alt text" />
                  </q-td>
                </template>

                <template v-slot:body-cell-caption="props">
                  <q-td :props="props">
                    <q-input v-model="props.row.caption" dense borderless placeholder="Caption" />
                  </q-td>
                </template>

                <template v-slot:body-cell-title="props">
                  <q-td :props="props">
                    <q-input v-model="props.row.title" dense borderless placeholder="Title" />
                  </q-td>
                </template>

                <template v-slot:body-cell-actions="props">
                  <q-td :props="props">
                    <q-btn
                      flat
                      dense
                      round
                      icon="delete"
                      color="negative"
                      @click="removeImage(props.row)"
                    >
                      <q-tooltip>Remove</q-tooltip>
                    </q-btn>
                  </q-td>
                </template>
              </q-table>
            </q-card>

            <q-banner v-else class="bg-grey-2 text-grey-7">
              <template v-slot:avatar>
                <q-icon name="image" />
              </template>
              No images uploaded yet.
            </q-banner>
          </q-tab-panel>

          <!-- SEO Tab (same as ProductCreate) -->
          <q-tab-panel name="seo">
            <div class="text-h6 q-mb-md">SEO Settings</div>

            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="product.meta_title"
                  label="Meta Title"
                  outlined
                  dense
                  counter
                  maxlength="255"
                  hint="Recommended: 50-60 characters"
                >
                  <template v-slot:prepend>
                    <q-icon name="title" />
                  </template>
                </q-input>
              </div>

              <div class="col-12">
                <q-input
                  v-model="product.meta_description"
                  label="Meta Description"
                  outlined
                  dense
                  type="textarea"
                  rows="3"
                  counter
                  maxlength="500"
                  hint="Recommended: 150-160 characters"
                >
                  <template v-slot:prepend>
                    <q-icon name="description" />
                  </template>
                </q-input>
              </div>

              <div class="col-12">
                <q-input
                  v-model="product.slug"
                  label="URL Slug"
                  outlined
                  dense
                  hint="Leave empty to auto-generate from product name"
                  @input="formatSlug"
                >
                  <template v-slot:prepend>
                    <q-icon name="link" />
                  </template>
                  <template v-slot:append>
                    <q-btn flat dense icon="refresh" @click="generateSlug">
                      <q-tooltip>Generate from name</q-tooltip>
                    </q-btn>
                  </template>
                </q-input>
              </div>
            </div>
          </q-tab-panel>
        </q-tab-panels>
      </q-card>

      <!-- Bottom Actions -->
      <div class="row justify-end q-mt-lg q-gutter-sm" v-if="!loading">
        <q-btn outline color="grey-7" label="Cancel" icon="close" @click="handleCancel" />
        <q-btn 
          unelevated 
          color="primary" 
          label="Update Product" 
          icon="save" 
          @click="handleUpdate"
          :loading="saving"
        />
      </div>
    </div>

    <!-- Add Products Dialog (same as ProductCreate) -->
    <q-dialog v-model="showProductDialog" persistent>
      <q-card style="min-width: 600px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Add Compatible Products</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-input
            v-model="productSearch"
            outlined
            dense
            label="Search products..."
            @input="searchProducts"
            debounce="300"
          >
            <template v-slot:prepend>
              <q-icon name="search" />
            </template>
          </q-input>

          <q-list bordered separator class="q-mt-md" style="max-height: 400px; overflow-y: auto">
            <q-item
              v-for="prod in filteredProducts"
              :key="prod.id"
              clickable
              @click="addCompatibleProduct(prod)"
              :disable="isProductAlreadyAdded(prod.id)"
            >
              <q-item-section>
                <q-item-label>{{ prod.name }}</q-item-label>
                <q-item-label caption>SKU: {{ prod.sku }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon 
                  :name="isProductAlreadyAdded(prod.id) ? 'check_circle' : 'add_circle'" 
                  :color="isProductAlreadyAdded(prod.id) ? 'positive' : 'primary'" 
                />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import axios from 'axios'

import { useRouter, useRoute } from 'vue-router'

export default {
  name: 'ProductEdit',

  setup() {
    const $q = useQuasar()
    const router = useRouter()
    const route = useRoute()

    const tab = ref('basic')
    const expandedCategories = ref([])
    const showProductDialog = ref(false)
    const productSearch = ref('')
    const editor = ref(null)
    const fileInput = ref(null)
    const loading = ref(true)
    const saving = ref(false)

    const product = ref({
      name: '',
      sku: '',
      short_description: '',
      description: '',
      categories: [],
      default_category_id: null,
      compatible_products: [],
      existing_images: [],
      new_images: [],
      deleted_images: [],
      meta_title: '',
      meta_description: '',
      slug: ''
    })

    const categoryTree = ref([])
    const categoryMap = ref({})
    const availableProducts = ref([])
    const filteredProducts = ref([])

    const compatibleColumns = [
      { name: 'name', label: 'Product Name', field: 'name', align: 'left' },
      { name: 'sku', label: 'SKU', field: 'sku', align: 'left' },
      { name: 'actions', label: 'Actions', align: 'center' }
    ]

    const imageColumns = [
      { name: 'preview', label: 'Preview', align: 'left' },
      { name: 'alt', label: 'Alt Text', field: 'alt', align: 'left' },
      { name: 'caption', label: 'Caption', field: 'caption', align: 'left' },
      { name: 'title', label: 'Title', field: 'title', align: 'left' },
      { name: 'actions', label: 'Actions', align: 'center' }
    ]

    const selectedCategoryOptions = computed(() => {
      return product.value.categories.map(id => ({
        label: getCategoryLabel(id),
        value: id
      }))
    })

    const compatibleProductsDetails = computed(() => {
      return product.value.compatible_products
        .map(id => availableProducts.value.find(p => p.id === id))
        .filter(p => p !== undefined)
    })

    const allImages = computed(() => {
      return [...product.value.existing_images, ...product.value.new_images]
    })

    watch(
      () => product.value.categories,
      (newVal) => {
        if (newVal.length > 0 && !product.value.default_category_id) {
          product.value.default_category_id = newVal[0]
        }
        if (!newVal.includes(product.value.default_category_id)) {
          product.value.default_category_id = newVal[0] || null
        }
      }
    )

    const fetchProductData = async () => {
      try {
        loading.value = true
        const uuid = route.params.uuid
        const response = await axios.get(`/admin/products/${uuid}/edit`)
        
        const data = response.data.data
        console.log(data);
        
        product.value = {
          ...data,
          existing_images: data.images || [],
          new_images: [],
          deleted_images: []
        }

        // Set editor content
        if (editor.value) {
          editor.value.innerHTML = data.description || ''
        }

      } catch (error) {
        console.error('Error fetching product:', error)
        $q.notify({
          type: 'negative',
          message: 'Failed to load product',
          caption: error.response?.data?.message || error.message
        })
        router.push('/products')
      } finally {
        loading.value = false
      }
    }

    const fetchCategories = async () => {
      try {
        const response = await axios.get('/admin/categories')
        categoryTree.value = buildCategoryTree(response.data)
        buildCategoryMap(response.data)
      } catch (error) {
        console.error('Error fetching categories:', error)
      }
    }

    const fetchProducts = async () => {
      try {
        const response = await axios.get('/admin/products')
        availableProducts.value = response.data.data
        filteredProducts.value = response.data.data
      } catch (error) {
        console.error('Error fetching products:', error)
      }
    }

    const buildCategoryTree = (categories) => {
      const catMap = {}
      const tree = []

      categories?.forEach(cat => {
        catMap[cat.id] = {
          id: cat.id,
          label: cat.name,
          icon: cat.icon || 'folder',
          color: cat.color || 'grey-6',
          children: []
        }
      })

      categories.forEach(cat => {
        if (cat.parent_id && catMap[cat.parent_id]) {
          catMap[cat.parent_id].children.push(catMap[cat.id])
        } else if (!cat.parent_id) {
          tree.push(catMap[cat.id])
        }
      })

      return tree
    }

    const buildCategoryMap = (categories) => {
      categories.forEach(cat => {
        categoryMap.value[cat.id] = cat
      })
    }

    const getCategoryLabel = (id) => {
      const category = categoryMap.value[id]
      if (!category) return id

      if (category.parent_id) {
        const parent = categoryMap.value[category.parent_id]
        return parent ? `${parent.name} > ${category.name}` : category.name
      }
      
      return category.name
    }

    const formatText = (command) => {
      document.execCommand(command, false, null)
    }

    const updateDescription = (event) => {
      product.value.description = event.target.innerHTML
    }

    const searchProducts = () => {
      if (!productSearch.value) {
        filteredProducts.value = [...availableProducts.value]
      } else {
        const search = productSearch.value.toLowerCase()
        filteredProducts.value = availableProducts.value.filter(
          p => p.name.toLowerCase().includes(search) || p.sku.toLowerCase().includes(search)
        )
      }
    }

    const openProductDialog = async () => {
      showProductDialog.value = true
      if (availableProducts.value.length === 0) {
        await fetchProducts()
      }
    }

    const addCompatibleProduct = (prod) => {
      if (!product.value.compatible_products.includes(prod.id)) {
        product.value.compatible_products.push(prod.id)
        $q.notify({
          type: 'positive',
          message: `${prod.name} added`,
          position: 'top'
        })
      }
    }

    const removeCompatibleProduct = (id) => {
      product.value.compatible_products = product.value.compatible_products.filter(pId => pId !== id)
    }

    const isProductAlreadyAdded = (id) => {
      return product.value.compatible_products.includes(id)
    }

    const triggerFileUpload = () => {
      fileInput.value.click()
    }

    const handleFileUpload = (event) => {
      const files = event.target.files
      for (let i = 0; i < files.length; i++) {
        const file = files[i]
        const reader = new FileReader()
        reader.onload = (e) => {
          product.value.new_images.push({
            id: Date.now() + i,
            file: file,
            preview: e.target.result,
            alt: '',
            caption: '',
            title: file.name,
            is_new: true
          })
        }
        reader.readAsDataURL(file)
      }
      event.target.value = ''
    }

    const removeImage = (image) => {
      if (image.is_new) {
        product.value.new_images = product.value.new_images.filter(img => img.id !== image.id)
      } else {
        product.value.deleted_images.push(image.id)
        product.value.existing_images = product.value.existing_images.filter(img => img.id !== image.id)
      }
    }

    const formatSlug = () => {
      product.value.slug = product.value.slug
        .toLowerCase()
        .replace(/[^a-z0-9-]/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '')
    }

    const generateSlug = () => {
      if (product.value.name) {
        product.value.slug = product.value.name
          .toLowerCase()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-')
      }
    }

    const autoGenerateSlug = () => {
      if (product.value.name && !product.value.slug) {
        generateSlug()
      }
    }

    const handleUpdate = async () => {
      if (!product.value.name || !product.value.sku) {
        $q.notify({
          type: 'negative',
          message: 'Please fill in required fields',
          position: 'top'
        })
        tab.value = 'basic'
        return
      }

      try {
        saving.value = true
        const formData = new FormData()
        
        formData.append('name', product.value.name)
        formData.append('sku', product.value.sku)
        formData.append('short_description', product.value.short_description || '')
        formData.append('description', product.value.description || '')
        formData.append('default_category_id', product.value.default_category_id)
        formData.append('slug', product.value.slug || '')
        formData.append('meta_title', product.value.meta_title || '')
        formData.append('meta_description', product.value.meta_description || '')

        product.value.categories.forEach((catId, index) => {
          formData.append(`categories[${index}]`, catId)
        })

        if (product.value.compatible_products.length > 0) {
          product.value.compatible_products.forEach((prodId, index) => {
            formData.append(`compatible_products[${index}]`, prodId)
          })
        }

        product.value.new_images.forEach((image, index) => {
          formData.append(`images[${index}][file]`, image.file)
          formData.append(`images[${index}][alt]`, image.alt || '')
          formData.append(`images[${index}][title]`, image.title || '')
          formData.append(`images[${index}][caption]`, image.caption || '')
        })

        if (product.value.deleted_images.length > 0) {
          product.value.deleted_images.forEach((id, index) => {
            formData.append(`deleted_images[${index}]`, id)
          })
        }

        formData.append('_method', 'PUT')

        const uuid = route.params.uuid
        await axios.post(`/admin/products/${uuid}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

        $q.notify({
          type: 'positive',
          message: 'Product updated successfully!',
          position: 'top',
          icon: 'check_circle'
        })

        router.push('/products')
        
      } catch (error) {
        console.error('Error updating product:', error)
        
        let errorMessage = 'Failed to update product'
        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat()
          errorMessage = errors.join(', ')
        }

        $q.notify({
          type: 'negative',
          message: 'Error',
          caption: errorMessage,
          position: 'top'
        })
      } finally {
        saving.value = false
      }
    }

    const handleCancel = () => {
      $q.dialog({
        title: 'Confirm',
        message: 'Discard changes?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        router.push('/products')
      })
    }

    onMounted(async () => {
      await Promise.all([
        fetchProductData(),
        fetchCategories()
      ])
    })

    return {
      tab, expandedCategories, showProductDialog, productSearch,
      editor, fileInput, loading, saving, product, categoryTree,
      availableProducts, filteredProducts, compatibleColumns, imageColumns,
      selectedCategoryOptions, compatibleProductsDetails, allImages,
      getCategoryLabel, formatText, updateDescription, searchProducts,
      openProductDialog, addCompatibleProduct, removeCompatibleProduct,
      isProductAlreadyAdded, triggerFileUpload, handleFileUpload, removeImage,
      formatSlug, generateSlug, autoGenerateSlug, handleUpdate, handleCancel
    }
  }
}
</script>

<style scoped>
.ellipsis-2-lines {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>