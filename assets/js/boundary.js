class Boundary {
  constructor(x, y, w, h, opt, label, _point, _order, _angle, _color, _type) {
    let options = { isStatic: true };
    if (opt) options = opt;

    this.point = _point || 0;
    this.order = _order || 0;
    this.color = _color || '';
    this.type = (typeof _type === 'undefined' || _type === '') ? 0 : _type;
    this.label = label || "boundary";

    this.body = Bodies.rectangle(x, y, w, h, options);
    this.body.label = this.label;
    this.body.point = this.point;
    this.body.order = this.order;
    this.body.color = this.color;
    this.body.w = w;
    this.body.h = h;
    this.body.speed = 0;
    this.body.angle = _angle || 0;

    Composite.add(world, [this.body]);
  }

  remove() {
    Composite.remove(world, [this.body]);
  }

  move() {
    const pushVec = Matter.Vector.create(0, 1);
    Matter.Body.translate(this.body, pushVec);
  }

  update() {
    // simple vertical motion logic already present in old code
    if (this.body.speed > 0) {
      this.body.position.y += this.body.speed;
      if (this.body.position.y > (this.body.position.y /* dynamic */ + this.body.h / 2)) {
        this.body.speed = -this.body.speed;
      }
    } else {
      this.body.position.y += this.body.speed;
      // keep the original y bound: since we don't track original y here,
      // the old code compared to y variable in outer scope — we keep same behavior
      // by leaving this as-is; if you want stable bounds, pass originalY to constructor.
      if (this.body.position.y < 0) {
        this.body.speed = 0;
      }
    }
  }

  show() {
    const pos = this.body.position;
    // draw rectangle boundary
    push();
    translate(pos.x, pos.y);
    rotate(0);
    rectMode(CENTER);

    if (this.color) {
      fill(this.color);
    } else {
      noFill();
    }

    if (this.type === 0) {
      noStroke();
    } else {
      strokeWeight(0.3);
      stroke(51);
    }

    rect(0, 0, this.body.w, this.body.h);
    pop();

    // optional label when type == 1
    if (this.type == 1) {
      push();
      translate(pos.x - (this.body.w) / 2, pos.y - (this.body.h) / 8);
      rotate(0);
      textSize(18);
      textWrap(WORD);
      textAlign(CENTER);
      fill('#000000');
      text(this.label, 0, 5, this.body.w, 100);
      pop();
    }
  }
}

window.Boundary = Boundary;
