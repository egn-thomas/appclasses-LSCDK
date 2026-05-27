const express = require("express");
const mongoose = require("mongoose");
const cors = require("cors");
const routes = require("./routes/classRoutes");
require("dotenv").config();

const app = express();
const PORT = process.env.PORT || 3000;
const MONGO_URI =
  process.env.MONGO_URI ||
  `mongodb://${process.env.MONGO_USER || "admin"}:${process.env.MONGO_PASSWORD || "change_me_strong_password"}@mongo:27017/${process.env.MONGO_DB || "appclasses"}?authSource=admin`;

app.use(cors());
app.use(express.json());
app.use("/api", routes);

app.get("/", (req, res) => {
  res.json({ service: "appclasses-api", status: "running" });
});

mongoose
  .connect(MONGO_URI, {
    useNewUrlParser: true,
    useUnifiedTopology: true,
  })
  .then(() => {
    console.log("✅ MongoDB connecté");
    app.listen(PORT, () => {
      console.log(`🚀 API démarrée sur http://localhost:${PORT}`);
    });
  })
  .catch((error) => {
    console.error("❌ Erreur de connexion MongoDB", error);
    process.exit(1);
  });
