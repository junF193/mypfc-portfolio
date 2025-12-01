<template>
  <div class="manual-entry">
    <div v-if="errorMessage" class="p-2 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
      {{ errorMessage }}
    </div>

    <form @submit.prevent="submitForm">
      <div class="mb-4">
        <label for="food_name" class="block mb-1 font-semibold text-sm">メニュー名</label>
        <input type="text" id="food_name" v-model="form.food_name" placeholder="メニューを入力" class="border p-2 w-full rounded text-sm" required>
      </div>

      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label for="energy_kcal_100g" class="block mb-1 font-semibold text-sm">カロリー</label>
          <div class="flex items-center">
            <input type="number" id="energy_kcal_100g" v-model.number="form.energy_kcal_100g" placeholder="0" class="border p-2 w-full rounded text-sm">
            <span class="ml-1 text-sm text-gray-600">kcal</span>
          </div>
        </div>
        <div>
          <label for="proteins_100g" class="block mb-1 font-semibold text-sm">タンパク質</label>
          <div class="flex items-center">
            <input type="number" id="proteins_100g" v-model.number="form.proteins_100g" placeholder="0" class="border p-2 w-full rounded text-sm">
            <span class="ml-1 text-sm text-gray-600">g</span>
          </div>
        </div>
        <div>
          <label for="fat_100g" class="block mb-1 font-semibold text-sm">脂質</label>
          <div class="flex items-center">
            <input type="number" id="fat_100g" v-model.number="form.fat_100g" placeholder="0" class="border p-2 w-full rounded text-sm">
            <span class="ml-1 text-sm text-gray-600">g</span>
          </div>
        </div>
        <div>
          <label for="carbohydrates_100g" class="block mb-1 font-semibold text-sm">炭水化物</label>
          <div class="flex items-center">
             <input type="number" id="carbohydrates_100g" v-model.number="form.carbohydrates_100g" placeholder="0" class="border p-2 w-full rounded text-sm">
             <span class="ml-1 text-sm text-gray-600">g</span>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="block mb-1 font-semibold text-sm">量（%）</label>
        <div class="flex flex-wrap gap-2 mb-2">
          <button type="button" v-for="p in [25, 50, 75, 100, 200]" :key="p"
                  @click="setPercent(p)"
                  class="px-3 py-1 rounded bg-gray-100 text-sm hover:bg-gray-200"
                  :class="{ 'bg-blue-100 text-blue-700 font-bold': form.multiplier === p / 100 }">
            {{ p }}%
          </button>
        </div>
        <div class="flex items-center gap-2">
          <input type="number" v-model.number="percentInput" min="1" max="9999" step="1" class="border p-2 rounded w-24 text-sm">
          <span class="text-sm text-gray-600">%</span>
        </div>
      </div>

      <div class="flex justify-end">
        <button type="submit" :disabled="isSubmitting" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600 disabled:opacity-50">
          {{ isSubmitting ? '登録中...' : '登録' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
export default {
  name: 'ManualEntry',
  props: {
    mealType: { type: String, required: true },
    date: { type: String, default: () => new Date().toISOString().slice(0, 10) }
  },
  data() {
    return {
      form: {
        food_name: '',
        energy_kcal_100g: null,
        proteins_100g: null,
        fat_100g: null,
        carbohydrates_100g: null,
        multiplier: 1.0,
      },
      percentInput: 100,
      isSubmitting: false,
      errorMessage: '',
    };
  },
  watch: {
    percentInput(val) {
      this.form.multiplier = val / 100;
    }
  },
  methods: {
    setPercent(p) {
      this.percentInput = p;
    },
    async submitForm() {
      this.isSubmitting = true;
      this.errorMessage = '';

      const payload = {
        ...this.form,
        meal_type: this.mealType,
        consumed_at: this.date,
        // API expects multiplier, which is already in form
      };

      try {
        await axios.get('/sanctum/csrf-cookie');
        const res = await axios.post('/api/food-logs', payload);
        
        this.$emit('registered', res.data.data);
        
        // Reset form
        this.form = {
          food_name: '',
          energy_kcal_100g: null,
          proteins_100g: null,
          fat_100g: null,
          carbohydrates_100g: null,
          multiplier: 1.0,
        };
        this.percentInput = 100;

      } catch (error) {
        console.error('Registration failed', error);
        if (error.response && error.response.data && error.response.data.message) {
             this.errorMessage = error.response.data.message;
             if (error.response.data.errors) {
                 this.errorMessage += ': ' + Object.values(error.response.data.errors).flat().join(', ');
             }
        } else {
            this.errorMessage = '登録に失敗しました。';
        }
      } finally {
        this.isSubmitting = false;
      }
    }
  }
}
</script>
