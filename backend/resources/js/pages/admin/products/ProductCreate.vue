<template>
  <q-page class="q-pa-md">
    <div class="q-pa-md" style="max-width: 1400px; margin: 0 auto">
      <!-- Header -->
      <div class="row items-center justify-between q-mb-lg">
        <div>
          <div class="text-h4 text-weight-bold text-grey-9">
            Create New Product
          </div>
          <div class="text-subtitle2 text-grey-6 q-mt-xs">
            Add a new product to your catalog
          </div>
        </div>
        <div class="row q-gutter-sm">
          <q-btn outline color="grey-7" label="Cancel" icon="close" @click="handleCancel" />
          <q-btn 
            unelevated 
            color="primary" 
            label="Save Product" 
            icon="save" 
            @click="handleSave"
            :loading="saving"
          />
        </div>
      </div>

      <!-- Loading State -->
      <q-inner-loading :showing="loading">
        <q-spinner-gears size="50px" color="primary" />
      </q-inner-loading>

      <!-- Tabs Card -->
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
          <q-tab name="suppliers" icon="local_shipping" label="Suppliers" />
          <q-tab v-if="type == 'standard'" name="price" icon="attach_money" label="Price" />
          <q-tab v-if="type == 'bundle'" name="bundle_and_price" icon="inventory" label="Bundle & Price" />
          <q-tab name="images" icon="image" label="Images" />

          <q-tab name="seo" icon="search" label="SEO" />
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="tab" animated>
          <!-- Basic Info Tab -->
          <q-tab-panel name="basic">
            <div class="text-h6 q-mb-md">Basic Information</div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-4">
                <q-input
                  v-model="product.name"
                  label="Product Name *"
                  outlined
                  dense
                  :error="!!formErrors.name"
                  :error-message="formErrors.name"
                  @blur="autoGenerateSlug"
                >
                  <template v-slot:prepend>
                    <q-icon name="title" />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-md-4">
                <q-input
                  v-model="product.sku"
                  label="SKU *"
                  outlined
                  dense
                  :error="!!formErrors.sku"
                  :error-message="formErrors.sku"
                >
                  <template v-slot:prepend>
                    <q-icon name="qr_code" />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-md-4">
                <q-select
                  v-model="product.status"
                  :options="['active', 'inactive', 'draft']"
                  label="Status *"
                  outlined
                  dense
                  :error="!!formErrors.status"
                  :error-message="formErrors.status"
                >
                  <template v-slot:prepend>
                    <q-icon name="toggle_on" />
                  </template>
                </q-select>
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
                  <!-- WYSIWYG Toolbar -->
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
                    <q-separator vertical inset />
                    <q-btn flat dense size="sm" icon="link" @click="insertLink">
                      <q-tooltip>Insert Link</q-tooltip>
                    </q-btn>
                  </q-bar>
                  <q-separator />
                  <div
                    ref="editor"
                    contenteditable="true"
                    class="q-pa-md editor-content"
                    style="min-height: 300px; outline: none"
                    @blur="updateDescription"
                  ></div>
                </q-card>
              </div>
            </div>
          </q-tab-panel>

          <!-- Categories Tab -->
          <q-tab-panel name="categories">
            <div class="text-h6 q-mb-md">Categories</div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <div class="text-subtitle2 q-mb-sm text-grey-8">
                  <q-icon name="account_tree" class="q-mr-xs" />
                  Category Tree
                </div>
                <q-card flat bordered :class="formErrors.categories ? 'error-border' : ''">
                  <q-inner-loading :showing="loadingCategories">
                    <q-spinner color="primary" size="3em" />
                  </q-inner-loading>
                  
                  <q-tree
                    v-if="!loadingCategories"
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
                <div v-if="formErrors.categories" class="text-negative text-caption q-mt-xs">
                  {{ formErrors.categories }}
                </div>
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
                  :error="!!formErrors.default_category_id"
                  :error-message="formErrors.default_category_id"
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

          <!-- Compatible Products Tab -->
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
              No compatible products added yet. Click "Add Products" to get started.
            </q-banner>
          </q-tab-panel>

          <!-- Standard Product - Price Tab -->
          <q-tab-panel name="price" v-if="type == 'standard'">
            <div class="text-h6 q-mb-md">Price & Cost Information</div>

            <div class="row q-col-gutter-md">
              <!-- Cost Mode and Base Settings -->
              <div class="col-12 col-md-4">
                <div class="text-subtitle2 q-mb-sm text-grey-8">Cost Mode</div>
                <q-btn-toggle
                  v-model="product.cost_mode"
                  spread
                  no-caps
                  unelevated
                  toggle-color="primary"
                  color="white"
                  text-color="primary"
                  :options="[
                    {label: 'Default Supplier', value: 'default'},
                    {label: 'Average Cost', value: 'average'}
                  ]"
                />
              </div>

              <!-- Override Shipping Cost -->
               <div class="col-12 col-md-4">
                <q-input
                  v-model.number="product.override_shipping_cost"
                  label="Override Shipping Cost"
                  outlined
                  dense
                  type="number"
                  step="0.01"
                  min="0"
                  prefix="$"
                  clearable
                >
                  <template v-slot:prepend>
                    <q-icon name="local_shipping" />
                  </template>
                </q-input>
              </div>

              <div class="col-12">
                <div class="row q-col-gutter-md">
                   <!-- GP Percentage -->
                  <div class="col-12 col-md-4">
                    <q-input
                      v-model.number="product.gp_percentage"
                      label="Gross Profit Percentage"
                      outlined
                      dense
                      type="number"
                      step="0.01"
                      min="0"
                      max="1000"
                      suffix="%"
                      :error="!!formErrors.gp_percentage"
                      :error-message="formErrors.gp_percentage"
                      @keypress="onlyNumbers"
                    >
                      <template v-slot:prepend>
                        <q-icon name="percent" />
                      </template>
                    </q-input>
                  </div>

                  <!-- Calculated RRP Cost (Read Only) -->
                  <div class="col-12 col-md-4">
                     <q-input
                      :model-value="calculatedPricing.sellingPrice ? calculatedPricing.sellingPrice.toFixed(2) : '0.00'"
                      label="RRP Cost"
                      outlined
                      dense
                      readonly
                      bg-color="grey-2"
                      prefix="$"
                    >
                      <template v-slot:prepend>
                        <q-icon name="calculate" />
                      </template>
                    </q-input>
                  </div>

                  <!-- Override RRP Cost -->
                  <div class="col-12 col-md-4">
                    <q-input
                      v-model.number="product.override_rrp_cost"
                      label="Override RRP Cost"
                      outlined
                      dense
                      type="number"
                      step="0.01"
                      min="0"
                      prefix="$"
                      clearable
                      :error="!!formErrors.override_rrp_cost || !!formErrors.price"
                      :error-message="formErrors.override_rrp_cost || formErrors.price"
                    >
                      <template v-slot:prepend>
                        <q-icon name="payments" />
                      </template>
                    </q-input>
                  </div>
                </div>
              </div>

              <!-- Calculation Summary Cards -->
              <div class="col-12">
                <div class="row q-col-gutter-md">
                  <div class="col-12 col-md-8">
                    <q-card flat bordered class="bg-blue-grey-1">
                      <q-card-section>
                        <div class="text-subtitle2 text-blue-grey-9 q-mb-md">
                          <q-icon name="analytics" class="q-mr-xs" />
                          Cost Breakdown ({{ product.cost_mode === 'default' ? 'Default Supplier' : 'Average' }})
                        </div>
                        <div class="row q-col-gutter-sm text-center">
                          <div class="col-3">
                            <div class="text-caption text-grey-7">Supplier Cost</div>
                            <div class="text-subtitle1 text-weight-medium">${{ calculatedPricing.supplierCost.toFixed(2) }}</div>
                          </div>
                          <div class="col-2">
                            <div class="text-caption text-grey-7">Duty</div>
                            <div class="text-subtitle1 text-weight-medium">{{ calculatedPricing.dutyPercentage.toFixed(2) }}%</div>
                          </div>
                          <div class="col-2">
                            <div class="text-caption text-grey-7">Duty Cost</div>
                            <div class="text-subtitle1 text-weight-medium">${{ calculatedPricing.dutyCost.toFixed(2) }}</div>
                          </div>
                          <div class="col-2">
                            <div class="text-caption text-grey-7">Shipping</div>
                            <div class="text-subtitle1 text-weight-medium">${{ calculatedPricing.shippingCost.toFixed(2) }}</div>
                          </div>
                          <div class="col-3">
                            <div class="text-caption text-blue-grey-9 text-weight-bold">Product Cost</div>
                            <div class="text-h6 text-blue-grey-10">${{ calculatedPricing.productCost.toFixed(2) }}</div>
                          </div>
                        </div>
                      </q-card-section>
                    </q-card>
                  </div>

                  <div class="col-12 col-md-4">
                    <q-card flat bordered class="bg-primary text-white">
                      <q-card-section>
                        <div class="text-subtitle2 q-mb-md">
                          <q-icon name="payments" class="q-mr-xs" />
                          Pricing Summary
                        </div>
                        <div class="row items-center justify-between q-mb-xs">
                          <div class="text-caption">Calculated RRP</div>
                          <div class="text-h5 text-weight-bold">${{ calculatedPricing.sellingPrice.toFixed(2) }}</div>
                        </div>
                        <q-separator dark class="q-my-sm" />
                        <div class="row items-center justify-between">
                          <div class="text-caption">Final RRP</div>
                          <div class="text-h6">${{ calculatedPricing.finalRRP.toFixed(2) }}</div>
                        </div>
                      </q-card-section>
                    </q-card>
                  </div>
                </div>
              </div>

              <div class="col-12" v-if="product.suppliers.length === 0">
                <q-banner dense class="bg-warning text-white rounded-borders">
                  <template v-slot:avatar>
                    <q-icon name="warning" />
                  </template>
                  No suppliers assigned. Product cost will be $0.00. Please assign suppliers in the "Suppliers" tab.
                </q-banner>
              </div>

              <!-- Customer Group Pricing Integrated Table -->
              <div class="col-12 q-mt-lg">
                <q-separator class="q-mb-md" />
                <div class="text-h6 q-mb-sm">Customer Group Pricing</div>
                <div class="text-subtitle2 text-grey-6 q-mb-md">
                  Set specific prices or discounts for different customer groups based on RRP.
                </div>

                <div v-if="customerGroups.length > 0" class="bg-white rounded-borders shadow-1 overflow-hidden">
                  <q-markup-table flat bordered dense class="q-table--no-wrap">
                    <thead>
                      <tr class="bg-grey-2 text-grey-8">
                        <th class="text-left">Customer Group</th>
                        <th class="text-left" style="width: 150px">Price Type</th>
                        <th class="text-left" style="width: 150px">Value</th>
                        <th class="text-right" style="width: 150px">Final Price</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="group in customerGroups" :key="group.id">
                        <td class="text-left">
                          <div class="text-weight-medium">{{ group.name }}</div>
                        </td>
                        <td class="text-left">
                          <q-select
                            v-model="getCustomerGroupPrice(group.id).price_type"
                            :options="[
                              { label: 'Fixed Price', value: 'fixed' },
                              { label: 'Percentage Discount', value: 'percentage' }
                            ]"
                            outlined
                            dense
                            emit-value
                            map-options
                            class="bg-grey-1"
                          />
                        </td>
                        <td class="text-left">
                          <q-input
                            v-model.number="getCustomerGroupPrice(group.id).amount"
                            outlined
                            dense
                            type="number"
                            step="0.01"
                            min="0"
                            @keypress="onlyNumbers"
                            clearable
                            :prefix="getCustomerGroupPrice(group.id).price_type === 'fixed' ? '$' : ''"
                            :suffix="getCustomerGroupPrice(group.id).price_type === 'percentage' ? '%' : ''"
                            class="bg-grey-1"
                          />
                        </td>
                        <td class="text-right">
                          <div class="text-subtitle2 text-primary font-mono text-weight-bold">
                            ${{ calculateGroupFinalPrice(getCustomerGroupPrice(group.id)).toFixed(2) }}
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </q-markup-table>
                </div>
                <q-banner v-else class="bg-grey-2 text-grey-7 rounded-borders">
                  <template v-slot:avatar>
                    <q-icon name="info" />
                  </template>
                  No customer groups found.
                </q-banner>
              </div>
            </div>
          </q-tab-panel>

          <!-- Bundle Product - Bundle & Price Tab -->
          <q-tab-panel name="bundle_and_price" v-if="type == 'bundle'">
            <div class="text-h6 q-mb-md">Bundle Products & Pricing</div>

            <!-- Search Standard Products -->
            <div class="q-mb-md">
              <q-input
                v-model="bundleProductSearch"
                outlined
                dense
                label="Search Standard Products and Add"
                placeholder="Type to search standard products..."
                @update:model-value="searchBundleProducts"
                debounce="500"
                clearable
              >
                <template v-slot:prepend>
                  <q-icon name="search" />
                </template>
              </q-input>

              <!-- Search Results Dropdown -->
              <q-list bordered separator v-if="bundleSearchResults.length > 0" class="q-mt-sm" style="max-height: 200px; overflow-y: auto">
                <q-item 
                  v-for="prod in bundleSearchResults" 
                  :key="prod.id"
                  clickable
                  @click="addBundleProduct(prod)"
                  :disable="isBundleProductAlreadyAdded(prod.id)"
                >
                  <q-item-section>
                    <q-item-label>{{ prod.name }}</q-item-label>
                    <q-item-label caption>SKU: {{ prod.sku }}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-icon 
                      :name="isBundleProductAlreadyAdded(prod.id) ? 'check_circle' : 'add_circle'" 
                      :color="isBundleProductAlreadyAdded(prod.id) ? 'positive' : 'primary'" 
                    />
                  </q-item-section>
                </q-item>
              </q-list>
            </div>

            <!-- Bundle Products Table -->
            <q-card flat bordered v-if="product.bundle_products.length > 0" :class="formErrors.bundle_products ? 'error-border' : ''">
              <q-table
                :rows="bundleProductsDetails"
                :columns="bundleColumns"
                row-key="id"
                flat
                hide-pagination
              >
                <template v-slot:body-cell-id="props">
                  <q-td :props="props">
                    {{ props.rowIndex + 1 }}
                  </q-td>
                </template>

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

                <template v-slot:body-cell-price="props">
                  <q-td :props="props">
                    <q-input
                      v-model.number="props.row.total_price"
                      type="number"
                      dense
                      outlined
                      prefix="$"
                      step="0.01"
                      min="0"
                      class="editable-input"
                      @update:model-value="updateBundleTotals"
                      @keypress="onlyNumbers"
                    />
                  </q-td>
                </template>

                <template v-slot:body-cell-qty="props">
                  <q-td :props="props">
                    <q-input
                      v-model.number="props.row.qty"
                      type="number"
                      dense
                      outlined
                      min="1"
                      class="editable-input"
                      @update:model-value="updateBundleTotals"
                      @keypress="onlyNumbers"
                    />
                  </q-td>
                </template>

                <template v-slot:body-cell-total="props">
                  <q-td :props="props">
                    <div class="text-weight-bold">
                      ${{ (parseFloat(props.row.total_price || 0) * parseInt(props.row.qty || 1)).toFixed(2) }}
                    </div>
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
                      @click="removeBundleProduct(props.row.id)"
                    >
                      <q-tooltip>Remove</q-tooltip>
                    </q-btn>
                  </q-td>
                </template>

                <template v-slot:bottom-row>
                  <q-tr class="bg-grey-2">
                    <q-td colspan="3" class="text-weight-bold">Subtotal</q-td>
                    <q-td class="text-weight-bold">{{ bundleTotals.totalQty }}</q-td>
                    <q-td class="text-weight-bold text-primary">${{ bundleTotals.subtotal.toFixed(2) }}</q-td>
                    <q-td></q-td>
                  </q-tr>
                </template>
              </q-table>
              <div v-if="formErrors.bundle_products" class="text-negative text-caption q-pa-sm">
                {{ formErrors.bundle_products }}
              </div>

              <q-card-section class="bg-grey-1">
                <div class="row q-col-gutter-md">
                  <div class="col-12 col-md-4">
                    <q-input
                      v-model.number="product.price"
                      label="Bundle Base Price"
                      outlined
                      dense
                      type="number"
                      step="0.01"
                      min="0"
                      prefix="$"
                      hint="Fixed base price added to components"
                      @keypress="onlyNumbers"
                    >
                      <template v-slot:prepend>
                        <q-icon name="attach_money" />
                      </template>
                    </q-input>
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input
                      v-model.number="product.bundle_gp_percentage"
                      label="Bundle GP % *"
                      outlined
                      dense
                      type="number"
                      step="0.01"
                      min="0"
                      max="100"
                      suffix="%"
                      hint="Gross profit percentage for the entire bundle"
                      :error="!!formErrors.bundle_gp_percentage"
                      :error-message="formErrors.bundle_gp_percentage"
                      @keypress="onlyNumbers"
                    >
                      <template v-slot:prepend>
                        <q-icon name="percent" />
                      </template>
                    </q-input>
                  </div>
                  <div class="col-12 col-md-6">
                    <q-card flat bordered class="bg-blue-1">
                      <q-card-section>
                        <div class="row q-col-gutter-sm">
                          <div class="col-4">
                            <div class="text-caption text-grey-7">Components Subtotal</div>
                            <div class="text-body1 text-weight-medium">${{ bundleTotals.subtotal.toFixed(2) }}</div>
                          </div>
                          <div class="col-4">
                            <div class="text-caption text-grey-7">Bundle Base Price</div>
                            <div class="text-body1 text-weight-medium">${{ parseFloat(product.price || 0).toFixed(2) }}</div>
                          </div>
                          <div class="col-4">
                            <div class="text-caption text-grey-7">Bundle GP %</div>
                            <div class="text-body1 text-weight-medium">{{ parseFloat(product.bundle_gp_percentage || 0).toFixed(2) }}%</div>
                          </div>
                          <div class="col-12 q-mt-sm">
                            <q-separator />
                          </div>
                          <div class="col-12">
                            <div class="text-caption text-grey-7">Final Bundle Price</div>
                            <div class="text-h6 text-primary text-weight-bold">${{ calculateBundleFinalPrice.toFixed(2) }}</div>
                          </div>
                        </div>
                      </q-card-section>
                    </q-card>
                  </div>
                </div>
              </q-card-section>
            </q-card>

            <q-banner v-else class="bg-grey-2 text-grey-7">
              <template v-slot:avatar>
                <q-icon name="inventory" />
              </template>
              No products added to bundle yet. Search and add standard products above.
            </q-banner>
          </q-tab-panel>

          <q-tab-panel name="images">
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6">Product Images</div>
              <q-btn
                color="primary"
                icon="add_photo_alternate"
                label="Upload Images"
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

            <draggable
              v-if="product.images.length > 0"
              v-model="product.images"
              item-key="id"
              handle=".handle"
              class="list-group"
              ghost-class="ghost"
            >
              <template #item="{ element, index }">
                <q-card flat bordered class="q-mb-sm q-pa-sm bg-grey-1">
                  <div class="row items-center no-wrap q-col-gutter-md">
                    <div class="col-auto">
                      <q-icon name="drag_indicator" class="handle cursor-pointer text-grey-7" size="24px" />
                    </div>
                    
                    <div class="col-auto">
                       <q-img 
                        :src="element.preview" 
                        style="width: 80px; height: 80px" 
                        class="rounded-borders bg-white"
                        fit="cover"
                       />
                    </div>
                    
                    <div class="col">
                        <div class="row q-col-gutter-sm">
                            <div class="col-12 col-md-4">
                                <q-input v-model="element.alt" dense outlined bg-color="white" label="Alt Text" />
                            </div>
                            <div class="col-12 col-md-4">
                                <q-input v-model="element.caption" dense outlined bg-color="white" label="Caption" />
                            </div>
                            <div class="col-12 col-md-4">
                                 <q-input v-model="element.title" dense outlined bg-color="white" label="Title" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-auto text-center">
                        <div class="text-caption text-grey-6 q-mb-xs">Primary</div>
                        <q-btn 
                            :icon="element.is_primary ? 'star' : 'star_border'" 
                            :color="element.is_primary ? 'warning' : 'grey-5'" 
                            round 
                            flat 
                            dense 
                            size="lg"
                            @click="setPrimaryImage(element)" 
                        >
                            <q-tooltip>{{ element.is_primary ? 'Primary Image' : 'Set as Primary' }}</q-tooltip>
                        </q-btn>
                    </div>
                    
                    <div class="col-auto">
                         <q-btn 
                            icon="delete" 
                            color="negative" 
                            flat 
                            round 
                            dense 
                            @click="removeImage(element.id)" 
                            :disable="element.is_primary && product.images.length > 1"
                        />
                    </div>
                  </div>
                </q-card>
              </template>
            </draggable>

            <q-banner v-else class="bg-grey-2 text-grey-7">
              <template v-slot:avatar>
                <q-icon name="image" />
              </template>
              No images uploaded yet. Click "Upload Images" to add product photos.
            </q-banner>
          </q-tab-panel>

          <!-- Suppliers Tab -->
          <q-tab-panel name="suppliers">
            <div class="text-h6 q-mb-md">Suppliers</div>

            <!-- Supplier Search -->
            <div class="q-mb-md">
              <q-select
                v-model="selectedSupplier"
                use-input
                hide-selected
                fill-input
                input-debounce="300"
                label="Search and Add Supplier"
                outlined
                dense
                :options="supplierOptions"
                @filter="filterSuppliers"
                @update:model-value="addSupplier"
              >
                <template v-slot:no-option>
                  <q-item>
                    <q-item-section class="text-grey">
                      No results
                    </q-item-section>
                  </q-item>
                </template>
                <template v-slot:prepend>
                  <q-icon name="search" />
                </template>
              </q-select>
            </div>

            <!-- Added Suppliers Table -->
             <q-card flat bordered v-if="product.suppliers && product.suppliers.length > 0">
              <q-table
                :rows="product.suppliers"
                :columns="supplierColumns"
                row-key="id"
                flat
                hide-pagination
              >
                <template v-slot:body-cell-price="props">
                  <q-td :props="props">
                    <q-input
                      v-model.number="props.row.price"
                      type="number"
                      dense
                      outlined
                      prefix="$"
                      step="0.01"
                      min="0"
                      class="editable-input"
                      @keypress="onlyNumbers"
                    />
                  </q-td>
                </template>

                <template v-slot:body-cell-duty_percentage="props">
                  <q-td :props="props">
                    <div class="text-caption">{{ props.row.duty_percentage }}%</div>
                  </q-td>
                </template>

                <template v-slot:body-cell-shipping_cost="props">
                  <q-td :props="props">
                    <div class="text-caption">${{ props.row.shipping_cost }}</div>
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
                      @click="removeSupplier(props.row.id)"
                    >
                      <q-tooltip>Remove</q-tooltip>
                    </q-btn>
                  </q-td>
                </template>
              </q-table>
            </q-card>

            <q-banner v-else class="bg-grey-2 text-grey-7">
              <template v-slot:avatar>
                <q-icon name="local_shipping" />
              </template>
              No suppliers added yet. Search and add suppliers above.
            </q-banner>

            <div v-if="product.suppliers.length > 0" class="q-mt-md">
              <div class="text-subtitle2 q-mb-sm text-grey-8">
                <q-icon name="star" class="q-mr-xs" />
                Default Supplier *
              </div>
              <q-select
                v-model="product.default_supplier_id"
                :options="product.suppliers.map(s => ({ label: s.name, value: s.id }))"
                outlined
                dense
                label="Select Default Supplier"
                hint="This supplier's cost will be used for 'Default Supplier' cost mode"
                emit-value
                map-options
                :error="!!formErrors.default_supplier_id"
                :error-message="formErrors.default_supplier_id"
              >
                 <template v-slot:prepend>
                  <q-icon name="local_shipping" />
                </template>
              </q-select>
            </div>
          </q-tab-panel>

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
                  :error="!!formErrors.slug"
                  :error-message="formErrors.slug"
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

              <div class="col-12">
                <q-card flat bordered class="bg-blue-1">
                  <q-card-section>
                    <div class="text-subtitle2 text-primary q-mb-sm">
                      <q-icon name="preview" class="q-mr-xs" />
                      Search Engine Preview
                    </div>
                    <div class="q-mt-md">
                      <div class="text-primary" style="font-size: 20px">
                        {{ product.meta_title || product.name || 'Product Title' }}
                      </div>
                      <div class="text-green-7 text-caption q-mt-xs">
                        {{ getFullUrl() }}
                      </div>
                      <div class="text-grey-8 text-body2 q-mt-xs">
                        {{ product.meta_description || product.short_description || 'Product description will appear here...' }}
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </q-tab-panel>

        </q-tab-panels>
      </q-card>


      <div class="row justify-end q-mt-lg q-gutter-sm" v-if="!loading">
        <q-btn outline color="grey-7" label="Cancel" icon="close" @click="handleCancel" />
        <q-btn 
          unelevated 
          color="primary" 
          label="Save Product" 
          icon="save" 
          @click="handleSave"
          :loading="saving"
        />
      </div>
    </div>

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
            @update:model-value="searchProducts"
            debounce="500"
            clearable
          >
            <template v-slot:prepend>
              <q-icon name="search" />
            </template>
          </q-input>

          <q-inner-loading :showing="loadingProducts">
            <q-spinner color="primary" size="3em" />
          </q-inner-loading>

          <q-list bordered separator class="q-mt-md" style="max-height: 400px; overflow-y: auto">
            <!-- Empty state when no search -->
            <q-item v-if="!productSearch && searchedProducts.length === 0 && !loadingProducts">
              <q-item-section>
                <q-item-label class="text-center text-grey-6">
                  <q-icon name="search" size="48px" color="grey-4" class="q-mb-sm" />
                  <div>Start typing to search for products</div>
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item
              v-for="prod in searchedProducts"
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

            <!-- No results found -->
            <q-item v-if="productSearch && searchedProducts.length === 0 && !loadingProducts">
              <q-item-section>
                <q-item-label class="text-center text-grey-6">
                  <q-icon name="search_off" size="48px" color="grey-4" class="q-mb-sm" />
                  <div>No products found for "{{ productSearch }}"</div>
                </q-item-label>
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
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import axios from 'axios'
import { useRouter, useRoute } from 'vue-router'
import draggable from 'vuedraggable'

export default {
  name: 'ProductCreate',
  components: {
    draggable
  },

  setup() {
    const $q = useQuasar()
    const router = useRouter()
    const route = useRoute()

    const type = route.query.type

    // Reactive data
    const tab = ref('basic')
    const expandedCategories = ref([])
    const showProductDialog = ref(false)
    const productSearch = ref('')
    const bundleProductSearch = ref('')
    const editor = ref(null)
    const fileInput = ref(null)
    const loading = ref(true)
    const loadingCategories = ref(false)
    const loadingProducts = ref(false)
    const loadingBundleProducts = ref(false)
    const saving = ref(false)
    const formErrors = ref({})
    const customerGroups = ref([])
    const customerGroupPricing = ref([])
    
    // Supplier specific refs
    const selectedSupplier = ref(null)
    const supplierOptions = ref([])
    const suppliersLoading = ref(false)

    const product = ref({
      name: '',
      sku: '',
      short_description: '',
      description: '',
      categories: [],
      default_category_id: null,
      compatible_products: [],
      bundle_products: [],
      price: 0, 
      product_cost: 0,
      gp_percentage: 0, 
      cost_mode: 'default',
      override_shipping_cost: null,
      rrp_cost: 0,
      override_rrp_cost: null,
      bundle_gp_percentage: 0,
      images: [],
      suppliers: [],
      meta_title: '',
      meta_description: '',
      slug: '',
      default_supplier_id: null,
      status: 'active'
    })

    const categoryTree = ref([])
    const categoryMap = ref({})
    const addedProductsCache = ref({}) 
    const bundleProductsCache = ref({})
    const searchedProducts = ref([]) 
    const bundleSearchResults = ref([])

    const compatibleColumns = [
      { name: 'name', label: 'Product Name', field: 'name', align: 'left' },
      { name: 'sku', label: 'SKU', field: 'sku', align: 'left' },
      { name: 'actions', label: 'Actions', align: 'center' }
    ]

    const bundleColumns = [
      { name: 'id', label: 'ID', align: 'left' },
      { name: 'name', label: 'Product Name', field: 'name', align: 'left' },
      { name: 'sku', label: 'SKU', field: 'sku', align: 'left' },
      { name: 'price', label: 'Actual Price', align: 'left' },
      { name: 'qty', label: 'QTY', align: 'left' },
      { name: 'total', label: 'Total', align: 'left' },
      { name: 'actions', label: 'Actions', align: 'center' }
    ]



    const supplierColumns = [
      { name: 'name', label: 'Supplier Name', field: 'name', align: 'left' },
      { name: 'price', label: 'Price', field: 'price', align: 'left' },
      { name: 'duty_percentage', label: 'Duty %', field: 'duty_percentage', align: 'left' },
      { name: 'shipping_cost', label: 'Shipping', field: 'shipping_cost', align: 'left' },
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
        .map(id => addedProductsCache.value[id])
        .filter(p => p !== undefined)
    })

    const bundleProductsDetails = computed(() => {
      return product.value.bundle_products
        .map(item => bundleProductsCache.value[item.id])
        .filter(p => p !== undefined)
    })

    

    const bundleTotals = computed(() => {
      let subtotal = 0
      let totalQty = 0

      

      product.value.bundle_products.forEach(item => {
        const prod = bundleProductsCache.value[item.id]
        if (prod) {
          const price = parseFloat(prod.total_price) || 0
          const qty = parseInt(prod.qty) || 1
          subtotal += price * qty
          totalQty += qty
        }
      })

      return {
        subtotal,
        totalQty
      }
    })

    const calculateStandardTotalPrice = computed(() => {
      const basePrice = parseFloat(product.value.price) || 0
      const gpPercentage = parseFloat(product.value.gp_percentage) || 0
      return basePrice + (basePrice * gpPercentage / 100)
    })

    const calculateBundleFinalPrice = computed(() => {
      const componentsSubtotal = bundleTotals.value.subtotal
      const basePrice = parseFloat(product.value.price) || 0
      const subtotal = componentsSubtotal + basePrice
      const bundleGP = parseFloat(product.value.bundle_gp_percentage) || 0
      return subtotal + (subtotal * bundleGP / 100)
    })

    const calculatedPricing = computed(() => {
      if (type !== 'standard') return {}
      
      let supplierCost = 0
      let dutyPercentage = 0
      let shippingCost = 0

      const assignedSuppliers = product.value.suppliers || []

      if (assignedSuppliers.length > 0) {
        if (product.value.cost_mode === 'default') {
          const def = assignedSuppliers.find(s => s.id === product.value.default_supplier_id) || assignedSuppliers[0]
          supplierCost = parseFloat(def.price) || 0
          dutyPercentage = parseFloat(def.duty_percentage) || 0
          shippingCost = parseFloat(def.shipping_cost) || 0
        } else {
          // Average Price, but use DEFAULT Supplier's Duty and Shipping
          const count = assignedSuppliers.length
          supplierCost = assignedSuppliers.reduce((sum, s) => sum + (parseFloat(s.price) || 0), 0) / count
          
          // Use default supplier for duty and shipping
          const def = assignedSuppliers.find(s => s.id === product.value.default_supplier_id) || assignedSuppliers[0]
          dutyPercentage = parseFloat(def.duty_percentage) || 0
          shippingCost = parseFloat(def.shipping_cost) || 0
        }
      }

      const dutyCost = supplierCost * (dutyPercentage / 100)
      const finalShippingCost = (product.value.override_shipping_cost !== null && product.value.override_shipping_cost !== undefined && product.value.override_shipping_cost !== '')
        ? parseFloat(product.value.override_shipping_cost) || 0
        : shippingCost

      const productCost = supplierCost + dutyCost + finalShippingCost
      
      const sellingPrice = productCost + (productCost * (parseFloat(product.value.gp_percentage) || 0) / 100)

      const finalRRP = (product.value.override_rrp_cost !== null && product.value.override_rrp_cost !== undefined && product.value.override_rrp_cost !== '')
        ? parseFloat(product.value.override_rrp_cost) || 0
        : sellingPrice

      return {
        supplierCost,
        dutyPercentage,
        dutyCost,
        shippingCost: finalShippingCost,
        productCost,
        sellingPrice,
        finalRRP
      }
    })



    const onlyNumbers = (evt) => {
      const charCode = evt.which ? evt.which : evt.keyCode
      if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
        evt.preventDefault()
      }
    }

    const fetchCategories = async () => {
      try {
        loadingCategories.value = true
        const response = await axios.get('/admin/categories')
   
        categoryTree.value = buildCategoryTree(response.data)
        buildCategoryMap(response.data)
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: 'Failed to load categories',
          caption: error.response?.data?.message || error.message
        })
      } finally {
        loadingCategories.value = false
      }
    }

    const fetchCustomerGroups = async () => {
      try {
        const response = await axios.get('/admin/customer-groups')
        customerGroups.value = response.data || []
        
        // Initialize pricing array if needed
        customerGroups.value.forEach(group => {
          if (!customerGroupPricing.value.find(p => p.customer_group_id === group.id)) {
            customerGroupPricing.value.push({
              customer_group_id: group.id,
              price: null,
              fixed_price: null,
              discount_percentage: null
            })
          }
        })
      } catch (error) {
        console.error('Failed to load customer groups:', error)
      }
    }

    const getCustomerGroupPrice = (groupId) => {
      let pricing = customerGroupPricing.value.find(p => p.customer_group_id === groupId)
      if (!pricing) {
        pricing = {
          customer_group_id: groupId,
          price_type: 'fixed',
          amount: null,
          price: null,
          fixed_price: null,
          discount_percentage: null
        }
        customerGroupPricing.value.push(pricing)
      }
      return pricing
    }

    const calculateGroupFinalPrice = (pricing) => {
      const basePrice = product.value.override_rrp_cost || (calculatedPricing.value ? calculatedPricing.value.finalRRP : 0) || 0
      if (pricing.price_type === 'fixed') {
        return parseFloat(pricing.amount || 0)
      } else {
        const discount = parseFloat(pricing.amount || 0)
        return basePrice - (basePrice * discount / 100)
      }
    }

    const fetchProducts = async (search = '') => {
      if (!search || search.trim().length < 2) {
        searchedProducts.value = []
        return
      }
      

      try {
        loadingProducts.value = true
        const response = await axios.get('/admin/products', { 
          params: { search: search.trim() } 
        })
        
        searchedProducts.value = response.data.data || []
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: 'Failed to load products',
          caption: error.response?.data?.message || error.message
        })
        searchedProducts.value = []
      } finally {
        loadingProducts.value = false
      }
    }

    const fetchBundleProducts = async (search = '') => {
      if (!search || search.trim().length < 2) {
        bundleSearchResults.value = []
        return
      }

      try {
        loadingBundleProducts.value = true
        const response = await axios.get('/admin/products', { 
          params: { 
            search: search.trim(),
            type: 'standard' 
          } 
        })
        
        bundleSearchResults.value = response.data.data || []
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: 'Failed to load products',
          caption: error.response?.data?.message || error.message
        })
        bundleSearchResults.value = []
      } finally {
        loadingBundleProducts.value = false
      }
    }

    const buildCategoryTree = (categories) => {
      const categoryMap = {}
      const tree = []

      categories?.forEach(cat => {
        categoryMap[cat.id] = {
          id: cat.id,
          label: cat.name,
          icon: cat.icon || 'folder',
          color: cat.color || 'grey-6',
          children: []
        }
      })

      categories.forEach(cat => {
        if (cat.parent_id) {
          if (categoryMap[cat.parent_id]) {
            categoryMap[cat.parent_id].children.push(categoryMap[cat.id])
          }
        } else {
          tree.push(categoryMap[cat.id])
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

    const formatText = (command) => {
      document.execCommand(command, false, null)
      editor.value?.focus()
    }

    const insertLink = () => {
      const url = prompt('Enter URL:')
      if (url) {
        document.execCommand('createLink', false, url)
        editor.value?.focus()
      }
    }

    const updateDescription = () => {
      if (editor.value) {
        product.value.description = editor.value.innerHTML
      }
    }

    const searchProducts = async (value) => {
      if (value && value.trim().length >= 2) {
        await fetchProducts(value)
      } else {
        searchedProducts.value = []
      }
    }

    const searchBundleProducts = async (value) => {
      if (value && value.trim().length >= 2) {
        await fetchBundleProducts(value)
      } else {
        bundleSearchResults.value = []
      }
    }

    const openProductDialog = () => {
      showProductDialog.value = true
      productSearch.value = ''
      searchedProducts.value = []
    }

    const addCompatibleProduct = (prod) => {
      if (!product.value.compatible_products.includes(prod.id)) {
        product.value.compatible_products.push(prod.id)
        addedProductsCache.value[prod.id] = prod
        
        $q.notify({
          type: 'positive',
          message: `${prod.name} added to compatible products`,
          position: 'top'
        })
      }
    }

    const removeCompatibleProduct = (id) => {
      product.value.compatible_products = product.value.compatible_products.filter(
        pId => pId !== id
      )
      
      $q.notify({
        type: 'info',
        message: 'Product removed',
        position: 'top'
      })
    }

    const isProductAlreadyAdded = (id) => {
      return product.value.compatible_products.includes(id)
    }

    const addBundleProduct = (prod) => {
      if (!isBundleProductAlreadyAdded(prod.id)) {
        const bundleItem = {
          id: prod.id,
          name: prod.name,
          sku: prod.sku,
          price: parseFloat(prod.total_price || 0),
          qty: 1
        }
        
        product.value.bundle_products.push(bundleItem)
        
        bundleProductsCache.value[prod.id] = {
          ...prod,
          total_price: prod.total_price,
          qty: 1
        }
        
        bundleProductSearch.value = ''
        bundleSearchResults.value = []
        
        $q.notify({
          type: 'positive',
          message: `${prod.name} added to bundle`,
          position: 'top'
        })
      }
    }

    const removeBundleProduct = (id) => {
      product.value.bundle_products = product.value.bundle_products.filter(
        item => item.id !== id
      )
      delete bundleProductsCache.value[id]
      
      $q.notify({
        type: 'info',
        message: 'Product removed from bundle',
        position: 'top'
      })
    }

    const isBundleProductAlreadyAdded = (id) => {
      return product.value.bundle_products.some(item => item.id === id)
    }

    const updateBundleTotals = () => {
      product.value.bundle_products = product.value.bundle_products.map(item => {
        const cached = bundleProductsCache.value[item.id]
        if (cached) {
          return {
            id: item.id,
            price: cached.total_price,
            qty: cached.qty
          }
        }
        return item
      })
    }

    const setPrimaryImage = (image) => {
      product.value.images.forEach(img => {
        img.is_primary = false
      })
      
      image.is_primary = true
      
      $q.notify({
        type: 'positive',
        message: 'Primary image updated',
        position: 'top'
      })
    }

    const triggerFileUpload = () => {
      fileInput.value.click()
    }

    const handleFileUpload = (event) => {
      const files = event.target.files
      const isFirstImage = product.value.images.length === 0
      
      for (let i = 0; i < files.length; i++) {
        const file = files[i]
        const reader = new FileReader()
        reader.onload = (e) => {
          product.value.images.push({
            id: Date.now() + i,
            file: file,
            preview: e.target.result,
            alt: '',
            caption: '',
            title: file.name,
            is_primary: isFirstImage && i === 0
          })
        }
        reader.readAsDataURL(file)
      }
      $q.notify({
        type: 'positive',
        message: `${files.length} image(s) uploaded`,
        position: 'top'
      })

      event.target.value = ''
    }

    const removeImage = (id) => {
      const imageToRemove = product.value.images.find(img => img.id === id)
      product.value.images = product.value.images.filter(img => img.id !== id)
      
      if (imageToRemove?.is_primary && product.value.images.length > 0) {
        product.value.images[0].is_primary = true
      }
      
      $q.notify({
        type: 'info',
        message: 'Image removed',
        position: 'top'
      })
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

    const getFullUrl = () => {
      const baseUrl = window.location.origin + '/products/'
      const category = product.value.default_category_id
        ? getCategoryLabel(product.value.default_category_id)
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '-') + '/'
        : ''
      const slug =
        product.value.slug ||
        (product.value.name
          ? product.value.name.toLowerCase().replace(/[^a-z0-9]/g, '-')
          : 'product-slug')
      return baseUrl + category + slug
    }

    const validateForm = () => {
      formErrors.value = {}

      if (!product.value.name) {
        formErrors.value.name = 'Product name is required'
      }

      if (!product.value.sku) {
        formErrors.value.sku = 'SKU is required'
      }

      if (product.value.categories.length === 0) {
        formErrors.value.categories = 'At least one category must be selected'
      }

      if (!product.value.default_category_id) {
        formErrors.value.default_category_id = 'Default category is required'
      }

      if (type === 'standard') {
        if (calculatedPricing.value.productCost <= 0) {
          formErrors.value.price = 'At least one supplier must be assigned with a price > 0'
        }
        if (product.value.gp_percentage < 0 || product.value.gp_percentage > 1000) {
          formErrors.value.gp_percentage = 'GP% must be between 0 and 1000'
        }
      }

      if (type === 'bundle') {
        if (product.value.bundle_products.length === 0) {
          formErrors.value.bundle_products = 'At least one product must be added to the bundle'
        }
        if (product.value.bundle_gp_percentage < 0 || product.value.bundle_gp_percentage > 100) {
          formErrors.value.bundle_gp_percentage = 'Bundle GP% must be between 0 and 100'
        }
      }

      return Object.keys(formErrors.value).length === 0
    }

    const scrollToFirstError = () => {
      nextTick(() => {
        const errorFields = {
          name: 'basic',
          sku: 'basic',
          type: 'basic',
          status: 'basic',
          short_description: 'basic',
          description: 'basic',
          categories: 'categories',
          default_category_id: 'categories',
          compatible_products: 'compatible',
          price: 'price',
          gp_percentage: 'price',
          bundle_products: 'bundle_and_price',
          bundle_gp_percentage: 'bundle_and_price',
          images: 'images',
          existing_images: 'images',
          deleted_images: 'images',
          suppliers: 'suppliers',
          slug: 'seo',
          meta_title: 'seo',
          meta_description: 'seo'
        }

        // Check for specific field errors or wildcard matches (like bundle_products.0.qty)
        for (const [field, tabName] of Object.entries(errorFields)) {
          const hasError = Object.keys(formErrors.value).some(key => 
            key === field || key.startsWith(`${field}.`)
          )
          
          if (hasError) {
            tab.value = tabName
            break
          }
        }
      })
    }

    const handleSave = async () => {
      if (!validateForm()) {
        scrollToFirstError()
        $q.notify({
          type: 'negative',
          message: 'Please fix the errors in the form',
          position: 'top'
        })
        return
      }

      try {
        saving.value = true

        const formData = new FormData()
        
        formData.append('name', product.value.name)
        formData.append('sku', product.value.sku)
        formData.append('type', type)
        formData.append('short_description', product.value.short_description || '')
        formData.append('description', product.value.description || '')
        formData.append('default_category_id', product.value.default_category_id)
        formData.append('slug', product.value.slug || '')
        formData.append('meta_title', product.value.meta_title || '')
        formData.append('meta_description', product.value.meta_description || '')
        formData.append('status', product.value.status)
        if (product.value.default_supplier_id) {
             formData.append('default_supplier_id', product.value.default_supplier_id)
        }

        if (product.value.type === 'standard' || type === 'standard') {
          formData.append('price', calculatedPricing.value.productCost)
          formData.append('gp_percentage', product.value.gp_percentage)
          formData.append('cost_mode', product.value.cost_mode)
          formData.append('override_shipping_cost', product.value.override_shipping_cost !== null ? product.value.override_shipping_cost : '')
          formData.append('rrp_cost', calculatedPricing.value.sellingPrice)
          formData.append('override_rrp_cost', product.value.override_rrp_cost !== null ? product.value.override_rrp_cost : '')
          formData.append('product_cost', calculatedPricing.value.productCost)
        }

        if (type === 'bundle') {
          product.value.bundle_products.forEach((item, index) => {
            formData.append(`bundle_products[${index}][id]`, item.id)
            formData.append(`bundle_products[${index}][price]`, item.price)
            formData.append(`bundle_products[${index}][qty]`, item.qty)
          })
          formData.append('bundle_gp_percentage', product.value.bundle_gp_percentage)
          formData.append('price', product.value.price)
          formData.append('bundle_subtotal', bundleTotals.value.subtotal)
          formData.append('bundle_final_price', calculateBundleFinalPrice.value)
        }

        product.value.categories.forEach((catId, index) => {
          formData.append(`categories[${index}]`, catId)
        })

        if (product.value.compatible_products.length > 0) {
          product.value.compatible_products.forEach((prodId, index) => {
            formData.append(`compatible_products[${index}]`, prodId)
          })
        }

        product.value.images.forEach((image, index) => {
          formData.append(`images[${index}][file]`, image.file)
          formData.append(`images[${index}][alt]`, image.alt || '')
          formData.append(`images[${index}][title]`, image.title || '')
          formData.append(`images[${index}][caption]`, image.caption || '')
          formData.append(`images[${index}][is_primary]`, image.is_primary ? 1 : 0)
        })

        if (product.value.suppliers && product.value.suppliers.length > 0) {
            product.value.suppliers.forEach((supplier, index) => {
                formData.append(`suppliers[${index}][id]`, supplier.id)
                formData.append(`suppliers[${index}][price]`, supplier.price)
            })
        }

        if (customerGroupPricing.value.length > 0) {
            const activePricing = customerGroupPricing.value.filter(p => p.amount != null && p.amount !== '')
            activePricing.forEach((p, index) => {
                formData.append(`customer_group_pricing[${index}][customer_group_id]`, p.customer_group_id)
                formData.append(`customer_group_pricing[${index}][price_type]`, p.price_type)
                formData.append(`customer_group_pricing[${index}][amount]`, p.amount)
            })
        }

        await axios.post('/admin/products', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        $q.notify({
          type: 'positive',
          message: 'Product created successfully!',
          position: 'top',
          icon: 'check_circle'
        })

        router.push('/products')
        
      } catch (error) {
        console.error('Error saving product:', error)
        
        if (error.response?.data?.errors) {
          formErrors.value = {}
          Object.keys(error.response.data.errors).forEach(key => {
            formErrors.value[key] = error.response.data.errors[key][0]
          })
          scrollToFirstError()
          
          const backendErrors = Object.values(error.response.data.errors).map(e => e[0]).join('. ')
          
          $q.notify({
            type: 'negative',
            message: `Validation failed: ${backendErrors}`,
            position: 'top',
            timeout: 5000
          })
        } else {
          let errorMessage = 'Failed to save product'
          
          if (error.response?.data?.message) {
            errorMessage = error.response.data.message
          } else if (error.message) {
            errorMessage = error.message
          }

          $q.notify({
            type: 'negative',
            message: 'Error',
            caption: errorMessage,
            position: 'top'
          })
        }
      } finally {
        saving.value = false
      }
    }

    const filterSuppliers = (val, update, abort) => {
      if (val.length < 2) {
        abort()
        return
      }

      update(() => {
        suppliersLoading.value = true
        axios.get('/admin/suppliers', { params: { search: val } })
          .then(response => {
            supplierOptions.value = response.data.suppliers.data.map(s => ({
              label: s.name,
              value: s.id,
              ...s
            }))
          })
          .catch(error => {
            console.error('Error fetching suppliers', error)
            $q.notify({
              type: 'negative',
              message: 'Failed to search suppliers'
            })
          })
          .finally(() => {
             suppliersLoading.value = false
          })
      })
    }

    const addSupplier = (supplier) => {
      if (!supplier) return
      
      const exists = product.value.suppliers.some(s => s.id === supplier.value)
      if (exists) {
        $q.notify({
          type: 'warning',
          message: 'Supplier already added'
        })
        selectedSupplier.value = null
        return
      }

      product.value.suppliers.push({
        id: supplier.value,
        name: supplier.label,
        price: product.value.price || 0,
        duty_percentage: parseFloat(supplier.duty_percentage) || 0,
        shipping_cost: parseFloat(supplier.shipping_cost) || 0,
        is_default: !!supplier.is_default
      })
      
      selectedSupplier.value = null
      
      $q.notify({
        type: 'positive',
        message: 'Supplier added'
      })
    }

    const removeSupplier = (id) => {
      product.value.suppliers = product.value.suppliers.filter(s => s.id !== id)
      $q.notify({
         type: 'info',
         message: 'Supplier removed'
      })
    }

    const checkAndAddDefaultSupplier = async () => {
      if (product.value.suppliers.length === 0) {
        try {
          const response = await axios.get('/admin/suppliers', { 
            params: { is_default: 1, status: 'active', per_page: 1 } 
          })
          if (response.data.suppliers.data && response.data.suppliers.data.length > 0) {
            const defaultSupplier = response.data.suppliers.data[0]
            addSupplier({
              label: defaultSupplier.name,
              value: defaultSupplier.id,
              ...defaultSupplier
            })
          }
        } catch (error) {
          console.error('Error fetching default supplier:', error)
        }
      }
    }

    watch(tab, (newTab) => {
      if (newTab === 'suppliers') {
        checkAndAddDefaultSupplier()
      }
    })

    watch(
      () => product.value.suppliers,
      (newVal) => {
         if (newVal.length > 0 && !product.value.default_supplier_id) {
            // If no default selected, select the first one (or the one marked is_default if available)
            const globalDefault = newVal.find(s => s.is_default)
            product.value.default_supplier_id = globalDefault ? globalDefault.id : newVal[0].id
         }
         // If selected default is gone, reset
         if (product.value.default_supplier_id && !newVal.find(s => s.id === product.value.default_supplier_id)) {
            product.value.default_supplier_id = newVal.length > 0 ? newVal[0].id : null
         }
      },
      { deep: true }
    )

    const handleCancel = () => {
      router.push('/products')
    }

    onMounted(async () => {
      try {
        loading.value = true
        await Promise.all([
          fetchCategories(),
          fetchCustomerGroups()
        ])
      } catch (error) {
        console.error('Error loading data:', error)
      } finally {
        loading.value = false
      }
    })

    return {
      tab,
      expandedCategories,
      showProductDialog,
      productSearch,
      bundleProductSearch,
      editor,
      fileInput,
      loading,
      loadingCategories,
      loadingProducts,
      loadingBundleProducts,
      saving,
      product,
      categoryTree,
      searchedProducts,
      bundleSearchResults,
      compatibleColumns,
      bundleColumns,
      selectedCategoryOptions,
      compatibleProductsDetails,
      bundleProductsDetails,
      bundleTotals,
      calculateStandardTotalPrice,
      calculateBundleFinalPrice,
      calculatedPricing,
      formErrors,
      getCategoryLabel,
      formatText,
      insertLink,
      updateDescription,
      searchProducts,
      searchBundleProducts,
      openProductDialog,
      addCompatibleProduct,
      removeCompatibleProduct,
      isProductAlreadyAdded,
      addBundleProduct,
      removeBundleProduct,
      isBundleProductAlreadyAdded,
      updateBundleTotals,
      setPrimaryImage,
      triggerFileUpload,
      handleFileUpload,
      removeImage,
      formatSlug,
      generateSlug,
      autoGenerateSlug,
      getFullUrl,
      handleSave,
      handleCancel,
      onlyNumbers,
      calculateGroupFinalPrice,
      selectedSupplier,
      supplierOptions,
      supplierColumns,
      filterSuppliers,
      addSupplier,
      removeSupplier,
      customerGroups,
      customerGroupPricing,
      getCustomerGroupPrice,

      type
    }
  }
}
</script>

<style scoped>
.editor-content:focus {
  outline: 2px solid #1976d2;
  outline-offset: -2px;
}

.editable-input {
  min-width: 100px;
}

.editable-input :deep(.q-field__control) {
  background-color: #f5f5f5;
}

.editable-input:hover :deep(.q-field__control) {
  background-color: #eeeeee;
}

.editable-input :deep(.q-field__control):focus-within {
  background-color: white;
  border-color: #1976d2;
}

.error-border {
  border: 1px solid #c10015;
}
</style>