<template>
  <q-page class="q-pa-md">
    <q-breadcrumbs class="q-mb-md">
      <q-breadcrumbs-el label="Dashboard" to="/" />
      <q-breadcrumbs-el label="Categories" to="/categories" />
      <q-breadcrumbs-el :label="isEdit ? 'Edit Category' : 'Add Category'" />
    </q-breadcrumbs>

    <q-card flat bordered>
      <q-card-section>
        <div class="text-h6">
          {{ isEdit ? 'Edit Category' : 'Add Category' }}
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-gutter-md">
        <q-input 
          v-model="form.name" 
          label="Category Name *" 
          outlined 
          dense 
          :rules="[val => !!val || 'Name is required']"
        />

         <div class="row items-center">
          <div class="col-auto q-mr-md">
            <q-toggle
              v-model="form.slug_status"
              label="Auto Slug"
              color="positive"
            />
          </div>
          <div class="text-caption text-grey-7">
            Auto Slug will generate slug from name
          </div>
        </div>

        <q-input 
          v-model="form.slug" 
          label="Slug *" 
          outlined 
          dense 
          :disable="form.slug_status"
          :hint="form.slug_status ? 'Auto-generated from category name' : 'Enter custom URL slug'"
          :rules="[val => !!val || 'Slug is required']"
          :bg-color="form.slug_status ? 'grey-2' : 'white'"
        >
          <template v-slot:append>
            <q-icon 
              v-if="!form.slug_status"
              name="refresh" 
              class="cursor-pointer" 
              @click="generateSlug"
            >
              <q-tooltip>Regenerate slug</q-tooltip>
            </q-icon>
          </template>
        </q-input>

        <q-select
          v-model="form.parent_id"
          :options="parentOptions"
          option-value="id"
          option-label="name"
          emit-value
          map-options
          label="Parent Category"
          outlined
          dense
          clearable
          hint="Select a parent category (optional)"
        />

        <div>
          <label class="text-subtitle2 q-mb-sm block">Description</label>
          <q-editor 
            v-model="form.description" 
            min-height="200px"
            :toolbar="[
              ['bold', 'italic', 'underline', 'strike'],
              ['link'],
              [
                {
                  label: $q.lang.editor.formatting,
                  icon: $q.iconSet.editor.formatting,
                  list: 'no-icons',
                  options: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']
                }
              ],
              ['quote', 'unordered', 'ordered'],
              ['undo', 'redo'],
              ['viewsource']
            ]"
          />
        </div>

        <q-separator class="q-my-md" />

        <div class="row items-center">
          <div class="col-auto q-mr-md">
            <q-toggle
              v-model="form.featured"
              label="Featured Category"
              color="positive"
            />
          </div>
          <div class="text-caption text-grey-7">
            Mark this category as featured to highlight it
          </div>
        </div>

        <div class="row items-center">
          <div class="col-auto q-mr-md">
            <q-toggle
              v-model="form.status"
              label="Active Status"
              color="positive"
            />
          </div>
          <div class="text-caption text-grey-7">
            Inactive categories won't be visible on the site
          </div>
        </div>

        <q-separator class="q-my-md" />

        <div>
          <label class="text-subtitle1 q-mb-sm block">Featured Image</label>
          
          <q-file 
            outlined 
            dense 
            accept="image/*"
            label="Choose image"
            @update:model-value="onImageSelect($event, 'featured')"
          >
            <template v-slot:prepend>
              <q-icon name="image" />
            </template>
          </q-file>

         <div v-if="featuredPreview" class="q-mt-md">
            <div class="relative-position" style="max-width: 300px;">
              <q-img 
                :src="featuredPreview" 
                class="rounded-borders"
                style="max-height: 200px;"
              />
              <q-btn
                round
                dense
                flat
                icon="close"
                color="white"
                class="absolute-top-right q-ma-sm"
                style="background: rgba(0,0,0,0.5);"
                @click="removeFeaturedImage"
              >
                <q-tooltip>Remove image</q-tooltip>
              </q-btn>
            </div>

            <div class="q-mt-md q-gutter-sm">
              <q-input 
                v-model="form.featured_image_meta.alt" 
                label="Alt Text" 
                outlined 
                dense 
                hint="Describe the image for accessibility"
              />
              <q-input 
                v-model="form.featured_image_meta.title" 
                label="Title" 
                outlined 
                dense 
              />
              <q-input 
                v-model="form.featured_image_meta.caption" 
                label="Caption" 
                outlined 
                dense 
                type="textarea"
                rows="2"
              />
            </div>
          </div>
        </div>

        <q-separator class="q-my-md" />

        <div>
          <label class="text-subtitle1 q-mb-sm block">Banner Image</label>
          
          <q-file 
            outlined 
            dense 
            accept="image/*"
            label="Choose banner"
            @update:model-value="onImageSelect($event, 'banner')"
          >
            <template v-slot:prepend>
              <q-icon name="panorama" />
            </template>
          </q-file>

          <div v-if="bannerPreview" class="q-mt-md">
            <div class="relative-position" style="max-width: 100%;">
              <q-img 
                :src="bannerPreview" 
                class="rounded-borders"
                style="max-height: 200px;"
              />
              <q-btn
                round
                dense
                flat
                icon="close"
                color="white"
                class="absolute-top-right q-ma-sm"
                style="background: rgba(0,0,0,0.5);"
                @click="removeBannerImage"
              >
                <q-tooltip>Remove banner</q-tooltip>
              </q-btn>
            </div>

            <div class="q-mt-md q-gutter-sm">
              <q-input 
                v-model="form.banner_image_meta.alt" 
                label="Alt Text" 
                outlined 
                dense 
                hint="Describe the banner for accessibility"
              />
              <q-input 
                v-model="form.banner_image_meta.title" 
                label="Title" 
                outlined 
                dense 
              />
              <q-input 
                v-model="form.banner_image_meta.caption" 
                label="Caption" 
                outlined 
                dense 
                type="textarea"
                rows="2"
              />
            </div>
          </div>
        </div>

        <q-separator class="q-my-md" />

        <div class="text-h6 q-mb-md">SEO Settings</div>

        <q-input 
          v-model="form.meta_title" 
          label="Meta Title" 
          outlined 
          dense 
          counter
          maxlength="60"
          hint="Recommended: 50-60 characters"
        />
        
        <q-input 
          v-model="form.meta_description" 
          label="Meta Description" 
          outlined 
          dense 
          type="textarea"
          rows="3"
          counter
          maxlength="160"
          hint="Recommended: 150-160 characters"
        />

        <div v-if="form.slug" class="q-mt-md">
          <label class="text-subtitle2 q-mb-xs block">URL Preview</label>
          <div class="text-grey-7 text-body2 bg-grey-2 q-pa-sm rounded-borders">
            {{ urlPreview }}
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="q-px-md q-py-md">
        <q-btn 
          flat 
          label="Cancel" 
          to="/categories" 
          color="grey-7"
        />
        <q-btn 
          unelevated
          color="primary" 
          :label="isEdit ? 'Update Category' : 'Create Category'"  
          :loading="loading" 
          @click="submitForm" 
        />
      </q-card-actions>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import axios from 'axios'
import slugify from 'slugify'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()

const isEdit = computed(() => !!route.params.uuid)
const loading = ref(false)
const parentOptions = ref([])

const form = ref({
  name: '',
  slug: '',
  description: '',
  parent_id: null,
  featured: false,
  status: true,
  slug_status: true, // Auto slug enabled by default

  featured_image: null,
  banner_image: null,

  featured_image_meta: {
    alt: '',
    title: '',
    caption: ''
  },

  banner_image_meta: {
    alt: '',
    title: '',
    caption: ''
  },

  meta_title: '',
  meta_description: ''
})

const featuredPreview = ref(null)
const bannerPreview = ref(null)

const urlPreview = computed(() => {
  const baseUrl = window.location.origin
  return `${baseUrl}/category/${form.value.slug}`
})

// Auto-generate slug when name or parent changes (only if auto slug is enabled)
watch([() => form.value.name, () => form.value.parent_id], () => {
  if (form.value.slug_status && form.value.name) {
    generateSlug()
  }
})

// Disable/enable slug field based on slug_status
watch(() => form.value.slug_status, (isAuto) => {
  if (isAuto && form.value.name) {
    generateSlug()
  }
})

function generateSlug() {
  if (!form.value.name) return

  let baseSlug = slugify(form.value.name, { 
    lower: true,
    strict: true,
    remove: /[*+~.()'"!:@]/g
  })

  // Add parent slug prefix if parent is selected
  if (form.value.parent_id) {
    const parent = parentOptions.value.find(p => p.id === form.value.parent_id)
    if (parent && parent.slug) {
      baseSlug = parent.slug + '-' + baseSlug
    }
  }

  form.value.slug = baseSlug
}

function onImageSelect(file, type) {
  if (!file) return
  const preview = URL.createObjectURL(file)

  if (type === 'featured') {
    form.value.featured_image = file
    featuredPreview.value = preview
    
    if (!form.value.featured_image_meta.alt) {
      form.value.featured_image_meta.alt = form.value.name || 'Category image'
    }
  }
  
  if (type === 'banner') {
    form.value.banner_image = file
    bannerPreview.value = preview
    
    if (!form.value.banner_image_meta.alt) {
      form.value.banner_image_meta.alt = `${form.value.name || 'Category'} banner`
    }
  }
}

function removeFeaturedImage() {
  form.value.featured_image = null
  featuredPreview.value = null
  form.value.featured_image_meta = { alt: '', title: '', caption: '' }
}

function removeBannerImage() {
  form.value.banner_image = null
  bannerPreview.value = null
  form.value.banner_image_meta = { alt: '', title: '', caption: '' }
}

async function fetchParentCategories() {
  try {
    const { data } = await axios.get('/admin/categories/parents', {
      params: { 
        exclude: isEdit.value ? route.params.uuid : null 
      }
    })
    parentOptions.value = data
  } catch (error) {
    console.error('Failed to fetch parent categories:', error)
  }
}

async function submitForm() {
  if (!form.value.name || !form.value.slug) {
    $q.notify({ 
      type: 'warning', 
      message: 'Please fill in all required fields' 
    })
    return
  }

  loading.value = true
  
  try {
    const fd = new FormData()

    Object.entries(form.value).forEach(([key, val]) => {
      if (val === null || val === undefined) return
      
      if (key === 'featured_image_meta' || key === 'banner_image_meta') {
        fd.append(key, JSON.stringify(val))
      } else if (key === 'featured' || key === 'status' || key === 'slug_status') {
        fd.append(key, val ? '1' : '0')
      } else if (val instanceof File) {
        fd.append(key, val)
      } else if (val !== '') {
        fd.append(key, val)
      }
    })

    let url = '/categories'
    if (isEdit.value) {
      fd.append('_method', 'PUT')
      url += '/' + route.params.uuid
    }

    await axios.post(url, fd, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    $q.notify({ 
      type: 'positive', 
      message: isEdit.value ? 'Category updated successfully' : 'Category created successfully',
      position: 'top'
    })
    
    router.push('/categories')
  } catch (error) {
    console.error(error)
    
    const message = error.response?.data?.message || 'Failed to save category'
    $q.notify({ 
      type: 'negative', 
      message,
      position: 'top'
    })
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchParentCategories()

  console.log('isEdit:', isEdit.value, 'route params:', route.params)

  if (isEdit.value) {
    try {
      const { data } = await axios.get(`/admin/categories/${route.params.uuid}/edit`)

      Object.assign(form.value, {
        name: data.name || '',
        slug: data.slug || '',
        description: data.description || '',
        parent_id: data.parent_id || null,
        featured: !!data.featured,
        status: data.status !== undefined ? !!data.status : true,
        slug_status: data.slug_status !== undefined ? !!data.slug_status : true,
        meta_title: data.meta_title || '',
        meta_description: data.meta_description || '',
        featured_image_meta: data.featured_image_meta || { alt: '', title: '', caption: '' },
        banner_image_meta: data.banner_image_meta || { alt: '', title: '', caption: '' }
      })

      if (data.featured_image) {
        featuredPreview.value = import.meta.env.VITE_APP_URL + data.featured_image
      }

      console.log(featuredPreview,"llll");
      

      if (data.banner_image) {
        bannerPreview.value = import.meta.env.VITE_APP_URL + data.banner_image
      }
      
    } catch (error) {
      console.error(error)
      $q.notify({ 
        type: 'negative', 
        message: 'Failed to load category data' 
      })
      router.push('/admin/categories')
    }
  }
})
</script>

<style scoped>
.block {
  display: block;
}
</style>