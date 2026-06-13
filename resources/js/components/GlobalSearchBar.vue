<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

const query = ref('')
const results = ref([])
const open = ref(false)
const loading = ref(false)
const selectedIndex = ref(-1)
let debounceTimer = null
let abortController = null

const badgeVariants = {
  PR: 'outline',
  RFQ: 'secondary',
  NOA: 'default',
  PO: 'destructive',
  'BAC Resolution': 'outline',
  Supplier: 'secondary',
  AIR: 'default',
  Transmittal: 'outline',
}

function doSearch(q) {
  if (abortController) {
    abortController.abort()
  }

  if (q.length < 2) {
    results.value = []
    open.value = false
    loading.value = false
    return
  }

  loading.value = true
  abortController = new AbortController()

  fetch(`/search?q=${encodeURIComponent(q)}`, {
    signal: abortController.signal,
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(data => {
      results.value = data
      open.value = data.length > 0
      selectedIndex.value = -1
      loading.value = false
    })
    .catch(() => {
      if (!abortController?.signal.aborted) {
        loading.value = false
      }
    })
}

function onInput(val) {
  query.value = val
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => doSearch(query.value), 250)
}

function onKeydown(e) {
  if (!open.value) return

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    selectedIndex.value = Math.min(selectedIndex.value + 1, results.value.length - 1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
  } else if (e.key === 'Enter' && selectedIndex.value >= 0) {
    e.preventDefault()
    navigateTo(results.value[selectedIndex.value])
  } else if (e.key === 'Escape') {
    open.value = false
    e.target.blur()
  }
}

function navigateTo(result) {
  open.value = false
  query.value = ''
  results.value = []
  router.visit(result.url)
}

function onClickOutside(e) {
  if (!e.target.closest('[data-search-bar]')) {
    open.value = false
  }
}

document.addEventListener('click', onClickOutside)

onUnmounted(() => {
  document.removeEventListener('click', onClickOutside)
  clearTimeout(debounceTimer)
  if (abortController) abortController.abort()
})
</script>

<template>
  <div data-search-bar class="relative w-full max-w-sm">
    <div class="relative">
      <Icon
        icon="radix-icons:magnifying-glass"
        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
      />
      <Input
        :model-value="query"
        placeholder="Search..."
        class="h-9 pl-9 pr-8"
        @update:model-value="onInput"
        @keydown="onKeydown"
        @focus="query.length >= 2 && results.length > 0 ? open = true : null"
      />
      <Icon
        v-if="loading"
        icon="radix-icons:update"
        class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground"
      />
    </div>

    <div
      v-if="open"
      class="absolute top-full z-50 mt-1 w-full rounded-md border bg-popover text-popover-foreground shadow-lg"
    >
      <div class="max-h-80 overflow-y-auto p-1">
        <button
          v-for="(result, i) in results"
          :key="`${result.type}-${result.label}`"
          type="button"
          class="flex w-full items-center gap-3 rounded-sm px-3 py-2.5 text-left text-sm transition-colors hover:bg-accent focus:outline-none"
          :class="i === selectedIndex ? 'bg-accent' : ''"
          @click="navigateTo(result)"
          @mouseenter="selectedIndex = i"
        >
          <Badge :variant="badgeVariants[result.type] || 'outline'" class="shrink-0">
            {{ result.type }}
          </Badge>
          <span class="min-w-0 truncate font-medium">{{ result.label }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
