import React from "react";
import PropTypes from "prop-types";
import dcDateWidget from "./dcDateWidget";

function dcDateTimeWidget(props) {
  const { dcDateWidget } = props.registry.widgets;
  return <dcDateWidget time {...props} />;
}

if (process.env.NODE_ENV !== "production") {
  dcDateTimeWidget.propTypes = {
    schema: PropTypes.object.isRequired,
    id: PropTypes.string.isRequired,
    value: PropTypes.string,
    required: PropTypes.bool,
    onChange: PropTypes.func,
    options: PropTypes.object,
  };
}

dcDateTimeWidget.defaultProps = {
  ...dcDateWidget.defaultProps,
  time: true,
};

export default dcDateTimeWidget;
