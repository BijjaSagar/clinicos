import 'dart:developer' as developer;

class EmrTemplate {
  final String specialty;
  final List<EmrSection> sections;

  EmrTemplate({
    required this.specialty,
    required this.sections,
  });

  factory EmrTemplate.fromJson(Map<String, dynamic> json) {
    developer.log('EmrTemplate.fromJson: Parsing template for ${json['specialty']}',
        name: 'EmrTemplate');

    return EmrTemplate(
      specialty: json['specialty'] ?? '',
      sections: (json['sections'] as List<dynamic>?)
              ?.map((s) => EmrSection.fromJson(s))
              .toList() ??
          [],
    );
  }

  Map<String, dynamic> toJson() {
    developer.log('EmrTemplate.toJson: Converting template to JSON',
        name: 'EmrTemplate');

    return {
      'specialty': specialty,
      'sections': sections.map((s) => s.toJson()).toList(),
    };
  }
}

class EmrSection {
  final String id;
  final String title;
  final String? type;
  final List<EmrField> fields;
  final List<Map<String, dynamic>>? commonDrugs;

  EmrSection({
    required this.id,
    required this.title,
    this.type,
    required this.fields,
    this.commonDrugs,
  });

  factory EmrSection.fromJson(Map<String, dynamic> json) {
    developer.log('EmrSection.fromJson: Parsing section ${json['id']}',
        name: 'EmrSection');

    return EmrSection(
      id: json['id'] ?? '',
      title: json['title'] ?? '',
      type: json['type'],
      fields: (json['fields'] as List<dynamic>?)
              ?.map((f) => EmrField.fromJson(f))
              .toList() ??
          [],
      commonDrugs: (json['common_drugs'] as List<dynamic>?)
          ?.map((d) => Map<String, dynamic>.from(d))
          .toList(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      if (type != null) 'type': type,
      'fields': fields.map((f) => f.toJson()).toList(),
      if (commonDrugs != null) 'common_drugs': commonDrugs,
    };
  }
}

class EmrField {
  final String name;
  final String type;
  final String label;
  final bool required;
  final String? placeholder;
  final List<dynamic>? options;
  final dynamic defaultValue;
  final List<dynamic>? condition;
  final int? min;
  final int? max;
  final int? step;
  final String? unit;
  final Map<String, dynamic>? components;
  final List<int>? range;
  final Map<String, dynamic>? extra;

  EmrField({
    required this.name,
    required this.type,
    required this.label,
    this.required = false,
    this.placeholder,
    this.options,
    this.defaultValue,
    this.condition,
    this.min,
    this.max,
    this.step,
    this.unit,
    this.components,
    this.range,
    this.extra,
  });

  factory EmrField.fromJson(Map<String, dynamic> json) {
    developer.log('EmrField.fromJson: Parsing field ${json['name']}',
        name: 'EmrField');

    return EmrField(
      name: json['name'] ?? '',
      type: json['type'] ?? 'text',
      label: json['label'] ?? '',
      required: json['required'] ?? false,
      placeholder: json['placeholder'],
      options: json['options'],
      defaultValue: json['default'],
      condition: json['condition'],
      min: json['min'],
      max: json['max'],
      step: json['step'],
      unit: json['unit'],
      components: json['components'] != null
          ? Map<String, dynamic>.from(json['components'])
          : null,
      range: json['range'] != null
          ? List<int>.from(json['range'])
          : null,
      extra: json,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'name': name,
      'type': type,
      'label': label,
      'required': required,
      if (placeholder != null) 'placeholder': placeholder,
      if (options != null) 'options': options,
      if (defaultValue != null) 'default': defaultValue,
      if (condition != null) 'condition': condition,
      if (min != null) 'min': min,
      if (max != null) 'max': max,
      if (step != null) 'step': step,
      if (unit != null) 'unit': unit,
      if (components != null) 'components': components,
      if (range != null) 'range': range,
    };
  }
}
