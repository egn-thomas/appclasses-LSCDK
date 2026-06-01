const ClassModel = require("../models/Class");

async function getAllClasses(req, res) {
  try {
    const classes = await ClassModel.find();
    res.json({ ok: true, classes });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

async function getClassById(req, res) {
  try {
    const schoolClass = await ClassModel.findById(req.params.id);
    if (!schoolClass) return res.status(404).json({ error: "Class not found" });
    res.json({ ok: true, class: schoolClass });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

async function createClass(req, res) {
  try {
    const { name } = req.body;

    if (!name) {
      return res.status(400).json({ error: "Missing class name" });
    }

    const existingClass = await ClassModel.findOne({ name });
    if (existingClass) {
      return res.status(400).json({ error: "Class name already exists" });
    }

    const schoolClass = new ClassModel({ name });
    await schoolClass.save();
    res.status(201).json({ ok: true, class: schoolClass.toObject() });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

async function updateClass(req, res) {
  try {
    const { id } = req.params;
    const { name } = req.body;

    const schoolClass = await ClassModel.findById(id);
    if (!schoolClass) return res.status(404).json({ error: "Class not found" });

    if (name) {
      const existingClass = await ClassModel.findOne({
        name,
        _id: { $ne: id },
      });
      if (existingClass) {
        return res.status(400).json({ error: "Class name already exists" });
      }
      schoolClass.name = name;
    }

    await schoolClass.save();
    res.json({ ok: true, class: schoolClass.toObject() });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

async function deleteClass(req, res) {
  try {
    const { id } = req.params;
    const result = await ClassModel.findByIdAndDelete(id);
    if (!result) return res.status(404).json({ error: "Class not found" });

    res.json({ ok: true });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

module.exports = {
  getAllClasses,
  getClassById,
  createClass,
  updateClass,
  deleteClass,
};
