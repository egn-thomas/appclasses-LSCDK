const express = require("express");
const mongoose = require("mongoose");
const cors = require("cors");
const session = require("express-session");
const MongoStore = require("connect-mongo");
const cookieParser = require("cookie-parser");
const routes = require("./routes/classRoutes");
const authRoutes = require("./routes/authRoutes");
const { requireAuth } = require("./middleware/authMiddleware");
const User = require("./models/User");
const bcrypt = require("bcrypt");
require("dotenv").config();

const app = express();
const PORT = process.env.PORT || 3000;
const MONGO_URI =
  process.env.MONGO_URI ||
  `mongodb://${process.env.MONGO_USER || "admin"}:${process.env.MONGO_PASSWORD || "change_me_strong_password"}@mongo:27017/${process.env.MONGO_DB || "appclasses"}?authSource=admin`;

app.use(
  cors({
    origin: process.env.FRONT_URL || "http://localhost",
    credentials: true,
  }),
);
app.use(express.json());
app.use(cookieParser());

app.use(
  session({
    name: "connect.sid",
    secret: process.env.SESSION_SECRET || "replace_this_with_env_secret",
    resave: false,
    saveUninitialized: false,
    store: MongoStore.create({ mongoUrl: MONGO_URI }),
    cookie: {
      httpOnly: true,
      secure: false,           // PHP appelle l'API en HTTP interne, pas HTTPS
      sameSite: "lax",
      maxAge: 1000 * 60 * 60 * 24,
    },
  }),
);

app.use("/api/auth", authRoutes);
// Protect all other API routes
app.use("/api", requireAuth, routes);

app.get("/", (req, res) => {
  res.json({ service: "appclasses-api", status: "running" });
});

mongoose
  .connect(MONGO_URI, {
    useNewUrlParser: true,
    useUnifiedTopology: true,
  })
  .then(async () => {
    console.log("✅ MongoDB connecté");

    // Create initial admin user if none
    const usersCount = await User.countDocuments();
    if (usersCount === 0) {
      const adminUser = process.env.ADMIN_USERNAME || "admin";
      const adminPass = process.env.ADMIN_PASSWORD || "adminpass";
      const hash = await bcrypt.hash(adminPass, 10);
      await User.create({ username: adminUser, password: hash, role: "admin" });
      console.log(`⚙️ Admin créé: ${adminUser} (change ADMIN_PASSWORD env)`);
    }

    app.listen(PORT, "0.0.0.0", () => {
      console.log(`🚀 API démarrée sur http://0.0.0.0:${PORT}`);
    });
  })
  .catch((error) => {
    console.error("❌ Erreur de connexion MongoDB", error);
    process.exit(1);
  });
  
app.use((req, res, next) => {
    console.log(`[${new Date().toISOString()}] ${req.method} ${req.path}`);
    console.log('  cookies:', req.cookies);
    console.log('  session:', req.session);
    next();
});
