import { ref, watch } from "vue";

export function useLocalStorage<T>(key: string, defaultValue: T) {
  const value = ref(defaultValue); // Let TS infer the type here

  const storedValue = localStorage.getItem(key);
  if (storedValue) {
    try {
      value.value = JSON.parse(storedValue);
    } catch (e) {
      console.error(`Error parsing localStorage key "${key}":`, e);
      localStorage.removeItem(key);
    }
  }

  watch(
    value,
    (newValue) => {
      localStorage.setItem(key, JSON.stringify(newValue));
    },
    { deep: true }
  );

  return value; // Return the ref directly
}
