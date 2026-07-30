const { defineConfig } = require("@playwright/test");

module.exports = defineConfig({
  testDir: "./tests/e2e",
  timeout: 60_000,
  use: {
    baseURL: "http://localhost:8888",
    trace: "retain-on-failure"
  },
  workers: 1
});
