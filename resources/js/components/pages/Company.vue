<template>
  <main class="container">
    <header class="header">
      <h1>Popular Manhwa</h1>
      <button @click="fetchManhwa" :disabled="loading" class="btn-refresh">
        {{ loading ? 'Loading...' : 'Refresh' }}
      </button>
    </header>

    <div v-if="error" class="alert-error">
      {{ error }}
    </div>

    <div v-if="loading" class="grid-skeleton">
      <div v-for="i in 8" :key="i" class="card-skeleton"></div>
    </div>

    <div v-else class="grid">
      <article v-for="item in manhwaList" :key="item.id" class="card">
        <div class="cover-wrapper">
          <img :src="item.coverUrl" :alt="item.title" loading="lazy" />
          <span class="status-badge" :class="item.status">{{ item.status }}</span>
        </div>
        <div class="card-content">
          <h2 class="title">{{ item.title }}</h2>
          <p class="description">{{ item.description }}</p>
        </div>
      </article>
    </div>
  </main>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const manhwaList = ref([]);
const loading = ref(true);
const error = ref(null);

// Your Laravel backend's own URL — NOT MangaDex directly
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

const fetchManhwa = async () => {
  loading.value = true;
  error.value = null;

  try {
    const { data } = await axios.get(`${API_BASE_URL}/api/manhwa`);

    manhwaList.value = data.data.map((manga) => {
      const coverRel = manga.relationships.find((r) => r.type === 'cover_art');
      const fileName = coverRel?.attributes?.fileName;

      return {
        id: manga.id,
        title: manga.attributes.title.en || Object.values(manga.attributes.title)[0] || 'Untitled',
        description: manga.attributes.description?.en || 'No description available.',
        status: manga.attributes.status,
        coverUrl: fileName
          ? `https://uploads.mangadex.org/covers/${manga.id}/${fileName}.256.jpg`
          : 'https://via.placeholder.com/256x360?text=No+Cover'
      };
    });
  } catch (err) {
    error.value = 'Failed to load Manhwa content. Please try again later.';
    console.error('Manhwa API Error:', err);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchManhwa();
});
</script>
<style scoped>
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  color: #1a202c;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 1.875rem;
  font-weight: 700;
  margin: 0;
}

.btn-refresh {
  background-color: #3182ce;
  color: #ffffff;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-refresh:hover:not(:disabled) {
  background-color: #2b6cb0;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.alert-error {
  padding: 1rem;
  background-color: #fed7d7;
  color: #9b2c2c;
  border-radius: 0.375rem;
  margin-bottom: 1.5rem;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1.5rem;
}

.card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.cover-wrapper {
  position: relative;
  width: 100%;
  aspect-ratio: 2 / 3;
  background-color: #edf2f7;
}

.cover-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.status-badge {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  border-radius: 0.25rem;
  background-color: rgba(0, 0, 0, 0.75);
  color: #ffffff;
}

.status-badge.ongoing { background-color: #2f855a; }
.status-badge.completed { background-color: #2b6cb0; }
.status-badge.cancelled { background-color: #9b2c2c; }

.card-content {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.title {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.description {
  font-size: 0.875rem;
  color: #4a5568;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.grid-skeleton {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1.5rem;
}

.card-skeleton {
  aspect-ratio: 2 / 3;
  background-color: #e2e8f0;
  border-radius: 0.5rem;
  animation: pulse 1.5s infinite ease-in-out;
}

@keyframes pulse {
  0% { opacity: 0.6; }
  50% { opacity: 1; }
  100% { opacity: 0.6; }
}
</style>