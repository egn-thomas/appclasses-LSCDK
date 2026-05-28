import { defineConfig } from "vite";

export default defineConfig({
  build: {
    outDir: "./src/dist",
    emptyOutDir: true,
    lib: {
      entry: "./src/assets/js/ChartsManager.ts",
      name: "ChartsManager",
      fileName: "charts-bundle",
      formats: ["iife"],
    },
    rollupOptions: {
      external: [],
    },
  },
});
