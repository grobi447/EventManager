<script setup lang="ts">
import Navbar from '@/components/navbar/Navbar.vue'
</script>

<template>
  <div>
    <Navbar />
    <main>
      <RouterView />
    </main>
  </div>
</template>