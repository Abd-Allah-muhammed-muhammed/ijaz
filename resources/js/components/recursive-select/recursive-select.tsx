import {Item} from './types'
import Option from "./option";
import {Form} from "react-bootstrap";
import './style.css'
import React from "react";
import {trans} from "@/hooks/use-translation";

type Props = React.HTMLAttributes<HTMLSelectElement> & {
  items: Item[],
}

const RecursiveSelect = ((props: Props) => {
  const {items, ...rest} = props;
  return (
    <Form.Select {...rest} className="RecursiveSelect">
      <option value="" className="empty">{trans('choose')}</option>
      {props.items.map(item => (
        <Option key={`RecursiveSelect-${Math.random()}-${item.id}`} item={item} level={1}/>
      ))}
    </Form.Select>
  );
});

export default RecursiveSelect
