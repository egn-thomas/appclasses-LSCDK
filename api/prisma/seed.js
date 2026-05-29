const mongoose = require("mongoose");
const bcrypt = require("bcrypt");
require("dotenv").config();

const User = require("../src/models/User");

async function main() {
  const MONGO_URI =
    process.env.MONGO_URI ||
    `mongodb://${process.env.MONGO_USER || "admin"}:${process.env.MONGO_PASSWORD || "change_me_strong_password"}@mongo:27017/${process.env.MONGO_DB || "appclasses"}?authSource=admin`;

  try {
    await mongoose.connect(MONGO_URI);
    console.log("Connected to MongoDB");

    // Créer l'utilisateur administrateur par défaut
    const adminUsername = process.env.ADMIN_USERNAME || "admin";
    const adminPassword = process.env.ADMIN_PASSWORD || "admin123";

    const existingAdmin = await User.findOne({ username: adminUsername });
    if (existingAdmin) {
      console.log(`User "${adminUsername}" already exists`);
      return;
    }

    const hashedPassword = await bcrypt.hash(adminPassword, 10);
    const admin = new User({
      username: adminUsername,
      password: hashedPassword,
      role: "Administrateur",
    });

    await admin.save();
    console.log(`Admin user "${adminUsername}" created successfully`);
  } catch (error) {
    console.error("Error in seed:", error);
    process.exit(1);
  } finally {
    await mongoose.connection.close();
  }
}

main();
