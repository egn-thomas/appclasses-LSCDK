const ClassModel = require("../models/Class");

exports.getHealth = (req, res) => {
  res.json({ status: "ok", env: process.env.NODE_ENV || "development" });
};

exports.getClasses = async (req, res) => {
  try {
    const classes = await ClassModel.find();
    res.json(classes);
  } catch (error) {
    res
      .status(500)
      .json({
        error: "Impossible de lire les classes",
        details: error.message,
      });
  }
};

exports.createClass = async (req, res) => {
  try {
    const newClass = new ClassModel({
      name: req.body.name,
      teacher: req.body.teacher || "",
      students: req.body.students || [],
    });
    const saved = await newClass.save();
    res.status(201).json(saved);
  } catch (error) {
    res
      .status(400)
      .json({ error: "Impossible de créer la classe", details: error.message });
  }
};
