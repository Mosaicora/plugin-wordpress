(function () {
  "use strict";

  const config = window.MosaicoraEditor;
  const rows = document.getElementById("mosaicora-role-rows");
  const addButton = document.getElementById("mosaicora-add-role");

  if (!config || !rows || !addButton) {
    return;
  }

  function groupRoles() {
    return config.roles.reduce(function (groups, role) {
      if (!groups[role.group]) {
        groups[role.group] = [];
      }
      groups[role.group].push(role);
      return groups;
    }, {});
  }

  function buildRoleSelect() {
    const select = document.createElement("select");
    select.className = "mosaicora-role-select";
    select.name = "mosaicora_semantic_role[]";

    Object.entries(groupRoles()).forEach(function (entry) {
      const optgroup = document.createElement("optgroup");
      optgroup.label = entry[0];
      entry[1].forEach(function (role) {
        const option = document.createElement("option");
        option.value = role.role;
        option.textContent = role.label;
        optgroup.appendChild(option);
      });
      select.appendChild(optgroup);
    });

    return select;
  }

  function selectedDefinition(select) {
    return config.roles.find(function (role) {
      return role.role === select.value;
    });
  }

  function buildControl(type) {
    const wrapper = document.createElement("div");
    wrapper.className = "mosaicora-role-control";
    let control;

    if (type === "boolean") {
      control = document.createElement("select");
      [["1", config.labels.yes], ["0", config.labels.no]].forEach(function (entry) {
        const option = document.createElement("option");
        option.value = entry[0];
        option.textContent = entry[1];
        control.appendChild(option);
      });
    } else {
      control = document.createElement("textarea");
      control.rows = type === "text" ? 2 : 4;
    }

    control.className = "widefat";
    control.name = "mosaicora_semantic_value[]";
    wrapper.appendChild(control);

    if (type === "list" || type === "metrics") {
      const help = document.createElement("span");
      help.className = "description";
      help.textContent = type === "list" ? config.labels.listHelp : config.labels.metricsHelp;
      wrapper.appendChild(help);
    }

    return wrapper;
  }

  function addRow() {
    const row = document.createElement("div");
    row.className = "mosaicora-role-row";
    const heading = document.createElement("div");
    heading.className = "mosaicora-role-row__heading";
    const select = buildRoleSelect();
    const type = document.createElement("span");
    type.className = "mosaicora-role-type";
    const remove = document.createElement("button");
    remove.type = "button";
    remove.className = "button-link-delete mosaicora-remove-role";
    remove.textContent = config.labels.remove;

    heading.append(select, type, remove);
    row.appendChild(heading);

    function refreshControl() {
      const definition = selectedDefinition(select);
      type.textContent = definition.type;
      const oldControl = row.querySelector(".mosaicora-role-control");
      const newControl = buildControl(definition.type);
      if (oldControl) {
        oldControl.replaceWith(newControl);
      } else {
        row.appendChild(newControl);
      }
    }

    refreshControl();
    rows.appendChild(row);
  }

  addButton.addEventListener("click", addRow);
  rows.addEventListener("click", function (event) {
    const remove = event.target.closest(".mosaicora-remove-role");
    if (remove) {
      remove.closest(".mosaicora-role-row").remove();
    }
  });

  rows.addEventListener("change", function (event) {
    if (!event.target.matches(".mosaicora-role-select")) {
      return;
    }
    const row = event.target.closest(".mosaicora-role-row");
    const definition = selectedDefinition(event.target);
    row.querySelector(".mosaicora-role-type").textContent = definition.type;
    row.querySelector(".mosaicora-role-control").replaceWith(buildControl(definition.type));
  });
})();
